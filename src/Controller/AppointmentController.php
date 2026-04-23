<?php

namespace App\Controller;

use App\Entity\CRM\Appointment;
use App\Repository\AppointmentRepository;
use App\Service\AppointmentService;
use App\Service\VicidialDbService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/appointments')]
class AppointmentController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly LoggerInterface $logger,
        private readonly AppointmentService $appointmentService,
        private readonly VicidialDbService $vicidialDbService
    ) {}

    #[Route('', name: 'appointment_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        $vicidialUser = $data['vicidialUser'] ?? null;
        $leadId = array_key_exists('leadId', $data) && $data['leadId'] !== '' ? (int) $data['leadId'] : null;
        $campaignId = array_key_exists('campaignId', $data) && $data['campaignId'] !== '' ? (string) $data['campaignId'] : null;
        $description = (string) ($data['description'] ?? '');

        if (!$vicidialUser) {
            return $this->json(['error' => 'vicidialUser manquant'], 400);
        }

        if (!$this->vicidialDbService->userExists($vicidialUser)) {
            return $this->json([
                'error' => 'Utilisateur Vicidial introuvable',
                'vicidialUser' => $vicidialUser,
            ], 404);
        }

        if ($leadId !== null && !$this->vicidialDbService->leadExists($leadId)) {
            return $this->json([
                'error' => 'Lead introuvable',
                'leadId' => $leadId,
            ], 404);
        }

        if ($campaignId !== null && !$this->vicidialDbService->campaignExists($campaignId)) {
            return $this->json([
                'error' => 'Campagne introuvable',
                'campaignId' => $campaignId,
            ], 404);
        }

        try {
            $start = new \DateTime($data['startTime'] ?? '');
            $end = new \DateTime($data['endTime'] ?? '');
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Format de date invalide'], 400);
        }

        try {
            $appointment = $this->appointmentService->createAppointment(
                $vicidialUser,
                $start,
                $end,
                $description,
                $leadId,
                $campaignId
            );

            return $this->json([
                'message' => 'Rendez-vous créé',
                'id' => $appointment->getId(),
            ], 201);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur création rendez-vous: ' . $e->getMessage());

            return $this->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
    #[Route('/{id}', name: 'appointment_get_by_id', methods: ['GET'])]
    public function getById(int $id): JsonResponse
    {
        $crmEm = $this->doctrine->getManager('crm');
    
        /** @var Appointment|null $appointment */
        $appointment = $crmEm->getRepository(Appointment::class)->find($id);
    
        if (!$appointment) {
            return $this->json(['error' => 'Rendez-vous non trouvé'], 404);
        }
    
        $user = null;
        $lead = null;
        $campaign = null;
    
        if ($appointment->getVicidialUser()) {
            $user = $this->vicidialDbService->getUserByUsername($appointment->getVicidialUser());
        }
    
        if ($appointment->getVicidialLeadId()) {
            $lead = $this->vicidialDbService->getLeadById($appointment->getVicidialLeadId());
        }
    
        if (method_exists($appointment, 'getVicidialCampaignId') && $appointment->getVicidialCampaignId()) {
            $campaign = $this->vicidialDbService->getCampaignById($appointment->getVicidialCampaignId());
        }
    
        $data = [
            'id' => $appointment->getId(),
            'startTime' => $appointment->getStartTime()?->format('Y-m-d H:i:s'),
            'endTime' => $appointment->getEndTime()?->format('Y-m-d H:i:s'),
            'description' => $appointment->getDescription(),
            'vicidialUser' => $appointment->getVicidialUser(),
            'vicidialLeadId' => $appointment->getVicidialLeadId(),
            'user' => $user,
            'lead' => $lead,
        ];
    
        if (method_exists($appointment, 'getVicidialCampaignId')) {
            $data['vicidialCampaignId'] = $appointment->getVicidialCampaignId();
            $data['campaign'] = $campaign;
        }
    
        return $this->json($data, 200);
    }
    
    #[Route('/userapp/{vicidialUser}', name: 'appointment_get_by_user', methods: ['GET'])]
    public function getByUser(string $vicidialUser, AppointmentRepository $appointmentRepository): JsonResponse
    {
        if (!$this->vicidialDbService->userExists($vicidialUser)) {
            return $this->json([
                'error' => 'Utilisateur Vicidial introuvable',
                'vicidialUser' => $vicidialUser,
            ], 404);
        }
    
        $appointments = $appointmentRepository->findByVicidialUser($vicidialUser);
        $data = [];
    
        foreach ($appointments as $a) {
            $lead = null;
            $campaign = null;
    
            if ($a->getVicidialLeadId()) {
                $lead = $this->vicidialDbService->getLeadById($a->getVicidialLeadId());
            }
    
            if (method_exists($a, 'getVicidialCampaignId') && $a->getVicidialCampaignId()) {
                $campaign = $this->vicidialDbService->getCampaignById($a->getVicidialCampaignId());
            }
    
            $row = [
                'id' => $a->getId(),
                'startTime' => $a->getStartTime()?->format('Y-m-d H:i:s'),
                'endTime' => $a->getEndTime()?->format('Y-m-d H:i:s'),
                'description' => $a->getDescription(),
                'vicidialUser' => $a->getVicidialUser(),
                'vicidialLeadId' => $a->getVicidialLeadId(),
                'user' => $this->vicidialDbService->getUserByUsername($a->getVicidialUser()),
                'lead' => $lead,
            ];
    
            if (method_exists($a, 'getVicidialCampaignId')) {
                $row['vicidialCampaignId'] = $a->getVicidialCampaignId();
                $row['campaign'] = $campaign;
            }
    
            $data[] = $row;
        }
    
        return $this->json($data, 200);
    }
    #[Route('/{id}', name: 'appointment_update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $crmEm = $this->doctrine->getManager('crm');

        /** @var Appointment|null $appointment */
        $appointment = $crmEm->getRepository(Appointment::class)->find($id);

        if (!$appointment) {
            return $this->json(['error' => 'Rendez-vous non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'JSON invalide'], 400);
        }

        $vicidialUser = array_key_exists('vicidialUser', $data)
            ? $data['vicidialUser']
            : $appointment->getVicidialUser();

        if (!$vicidialUser) {
            return $this->json(['error' => 'vicidialUser manquant'], 400);
        }

        if (!$this->vicidialDbService->userExists($vicidialUser)) {
            return $this->json([
                'error' => 'Utilisateur Vicidial introuvable',
                'vicidialUser' => $vicidialUser,
            ], 404);
        }

        if (array_key_exists('leadId', $data)) {
            if ($data['leadId'] === null || $data['leadId'] === '') {
                $leadId = null;
            } else {
                $leadId = (int) $data['leadId'];

                if (!$this->vicidialDbService->leadExists($leadId)) {
                    return $this->json([
                        'error' => 'Lead introuvable',
                        'leadId' => $leadId,
                    ], 404);
                }
            }
        } else {
            $leadId = $appointment->getVicidialLeadId();
        }

        if (array_key_exists('campaignId', $data)) {
            if ($data['campaignId'] === null || $data['campaignId'] === '') {
                $campaignId = null;
            } else {
                $campaignId = (string) $data['campaignId'];

                if (!$this->vicidialDbService->campaignExists($campaignId)) {
                    return $this->json([
                        'error' => 'Campagne introuvable',
                        'campaignId' => $campaignId,
                    ], 404);
                }
            }
        } else {
            $campaignId = method_exists($appointment, 'getVicidialCampaignId')
                ? $appointment->getVicidialCampaignId()
                : null;
        }

        try {
            $start = array_key_exists('startTime', $data)
            ? new \DateTime($data['startTime'])
            : $appointment->getStartTime();
        
        $end = array_key_exists('endTime', $data)
            ? new \DateTime($data['endTime'])
            : $appointment->getEndTime();
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Format de date invalide'], 400);
        }

        $description = array_key_exists('description', $data)
            ? (string) $data['description']
            : $appointment->getDescription();

        try {
            $this->appointmentService->updateAppointment(
                $appointment,
                $vicidialUser,
                $start,
                $end,
                $description,
                $leadId,
                $campaignId
            );

            return $this->json(['message' => 'Rendez-vous mis à jour'], 200);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur mise à jour rendez-vous: ' . $e->getMessage());

            return $this->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    #[Route('', name: 'appointment_list_all', methods: ['GET'])]
    public function listAll(AppointmentRepository $appointmentRepository): JsonResponse
    {
        $appointments = $appointmentRepository->findAll();
        $data = [];

        foreach ($appointments as $a) {
            $user = null;
            $lead = null;
            $campaign = null;

            if ($a->getVicidialUser()) {
                $user = $this->vicidialDbService->getUserByUsername($a->getVicidialUser());
            }

            if ($a->getVicidialLeadId()) {
                $lead = $this->vicidialDbService->getLeadById($a->getVicidialLeadId());
            }

            if (method_exists($a, 'getVicidialCampaignId') && $a->getVicidialCampaignId()) {
                $campaign = $this->vicidialDbService->getCampaignById($a->getVicidialCampaignId());
            }

            $row = [
                'id' => $a->getId(),
                'startTime' => $a->getStartTime()?->format('Y-m-d H:i:s'),
                'endTime' => $a->getEndTime()?->format('Y-m-d H:i:s'),
                'description' => $a->getDescription(),
                'vicidialUser' => $a->getVicidialUser(),
                'vicidialLeadId' => $a->getVicidialLeadId(),
                'user' => $user,
                'lead' => $lead,
            ];

            if (method_exists($a, 'getVicidialCampaignId')) {
                $row['vicidialCampaignId'] = $a->getVicidialCampaignId();
                $row['campaign'] = $campaign;
            }

            $data[] = $row;
        }

        return $this->json($data);
    }

    #[Route('/{id}', name: 'appointment_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $crmEm = $this->doctrine->getManager('crm');

        /** @var Appointment|null $appointment */
        $appointment = $crmEm->getRepository(Appointment::class)->find($id);

        if (!$appointment) {
            return $this->json(['error' => 'Rendez-vous non trouvé'], 404);
        }

        $crmEm->remove($appointment);
        $crmEm->flush();

        return $this->json(['message' => 'Rendez-vous supprimé'], 200);
    }
}