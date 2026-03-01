<?php

namespace App\Controller;

use App\Entity\CRM\Appointment;
use App\Entity\CRM\CrmUser;
use App\Entity\Vicidial\CrmLead;
use App\Repository\AppointmentRepository;
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
        private readonly LoggerInterface $logger
    ) {}

    #[Route('', name: 'appointment_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $crmEm = $this->doctrine->getManager('crm');
        $vicidialEm = $this->doctrine->getManager('vicidial');

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        $userId = $data['userId'] ?? null;
        if (!$userId) {
            return $this->json(['error' => 'userId manquant'], 400);
        }

        /** @var CrmUser|null $user */
        $user = $crmEm->getRepository(CrmUser::class)->find((int)$userId);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable', 'userId' => $userId], 404);
        }

        $leadId = $data['leadId'] ?? null;
        if ($leadId !== null) {
            $lead = $vicidialEm->getRepository(CrmLead::class)->find((int)$leadId);
            if (!$lead) {
                return $this->json(['error' => 'Lead introuvable', 'leadId' => $leadId], 404);
            }
        }

        try {
            $start = new \DateTime($data['startTime'] ?? '');
            $end   = new \DateTime($data['endTime'] ?? '');
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Format de date invalide'], 400);
        }

        $appointment = new Appointment();
        $appointment->setCrmUser($user);
        $appointment->setDescription((string)($data['description'] ?? ''));
        $appointment->setStartTime($start);
        $appointment->setEndTime($end);
        $appointment->setVicidialLeadId($leadId !== null ? (int)$leadId : null);

        $crmEm->persist($appointment);
        $crmEm->flush();

        return $this->json(['message' => 'Rendez-vous créé', 'id' => $appointment->getId()], 201);
    }

    #[Route('/{id}', name: 'appointment_update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $crmEm = $this->doctrine->getManager('crm');
        $vicidialEm = $this->doctrine->getManager('vicidial');

        /** @var Appointment|null $appointment */
        $appointment = $crmEm->getRepository(Appointment::class)->find($id);
        if (!$appointment) {
            return $this->json(['error' => 'Rendez-vous non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'JSON invalide'], 400);
        }

        if (isset($data['userId'])) {
            $user = $crmEm->getRepository(CrmUser::class)->find((int)$data['userId']);
            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable', 'userId' => $data['userId']], 404);
            }
            $appointment->setCrmUser($user);
        }

        if (array_key_exists('leadId', $data)) {
            $leadId = $data['leadId'];
            if ($leadId === null || $leadId === '') {
                $appointment->setVicidialLeadId(null);
            } else {
                $lead = $vicidialEm->getRepository(CrmLead::class)->find((int)$leadId);
                if (!$lead) {
                    return $this->json(['error' => 'Lead introuvable', 'leadId' => $leadId], 404);
                }
                $appointment->setVicidialLeadId((int)$leadId);
            }
        }

        if (isset($data['description'])) {
            $appointment->setDescription((string)$data['description']);
        }

        if (isset($data['startTime'])) {
            try { $appointment->setStartTime(new \DateTime($data['startTime'])); }
            catch (\Throwable $e) { return $this->json(['error' => 'startTime invalide'], 400); }
        }

        if (isset($data['endTime'])) {
            try { $appointment->setEndTime(new \DateTime($data['endTime'])); }
            catch (\Throwable $e) { return $this->json(['error' => 'endTime invalide'], 400); }
        }

        $crmEm->flush();

        return $this->json(['message' => 'Rendez-vous mis à jour'], 200);
    }

    #[Route('', name: 'appointment_list_all', methods: ['GET'])]
    public function listAll(AppointmentRepository $appointmentRepository): JsonResponse
    {
        $vicidialEm = $this->doctrine->getManager('vicidial');

        $appointments = $appointmentRepository->findAll();

        // collect leadIds
        $leadIds = [];
        foreach ($appointments as $a) {
            if ($a->getVicidialLeadId()) $leadIds[] = $a->getVicidialLeadId();
        }
        $leadIds = array_values(array_unique($leadIds));

        // fetch leads
        $leadsById = [];
        if ($leadIds) {
            $leads = $vicidialEm->getRepository(CrmLead::class)->findBy(['id' => $leadIds]);
            foreach ($leads as $l) $leadsById[$l->getId()] = $l;
        }

        $data = [];
        foreach ($appointments as $a) {
            $lead = null;
            $leadId = $a->getVicidialLeadId();
            if ($leadId && isset($leadsById[$leadId])) {
                $l = $leadsById[$leadId];
                $lead = [
                    'id' => $l->getId(),
                    'firstName' => $l->getFirstName(),
                    'lastName' => $l->getLastName(),
                    'phoneNumber' => $l->getPhoneNumber(),
                    'email' => $l->getEmail(),
                ];
            }

            $data[] = [
                'id' => $a->getId(),
                'startTime' => $a->getStartTime()?->format('Y-m-d H:i:s'),
                'endTime' => $a->getEndTime()?->format('Y-m-d H:i:s'),
                'description' => $a->getDescription(),
                'user' => $a->getCrmUser() ? [
                    'id' => $a->getCrmUser()->getId(),
                    'username' => $a->getCrmUser()->getUsername(),
                    'fullName' => $a->getCrmUser()->getFullName(),
                ] : null,
                'vicidialLeadId' => $leadId,
                'lead' => $lead,
            ];
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

        // si tu veux supprimer aussi notes/tasks: à faire ici
        $crmEm->remove($appointment);
        $crmEm->flush();

        return $this->json(['message' => 'Rendez-vous supprimé'], 200);
    }
}