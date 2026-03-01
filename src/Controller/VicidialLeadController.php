<?php

namespace App\Controller;

use App\Entity\Vicidial\CrmLead;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/vicidial-leads')]
class VicidialLeadController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $registry,
    ) {}

    private function vicidialEm()
    {
        return $this->registry->getManager('vicidial');
    }

    /**
     * GET /api/vicidial-leads
     */
    #[Route('', name: 'vicidial_lead_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $em = $this->vicidialEm();
        $leads = $em->getRepository(CrmLead::class)->findAll();

        if (!$leads) {
            return $this->json([
                'status' => false,
                'message' => 'Aucun lead trouvé.'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = array_map(static function (CrmLead $lead): array {
            return [
                'id' => $lead->getId(),
                'firstName' => $lead->getFirstName(),
                'lastName' => $lead->getLastName(),
                'phoneNumber' => $lead->getPhoneNumber(),
                'email' => $lead->getEmail(),
            ];
        }, $leads);

        return $this->json($data, Response::HTTP_OK);
    }

    /**
     * POST /api/vicidial-leads
     */
    #[Route('', name: 'vicidial_lead_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['phone_number'])) {
            return $this->json([
                'error' => 'first_name, last_name, phone_number sont obligatoires'
            ], Response::HTTP_BAD_REQUEST);
        }

        $lead = new CrmLead();
        $lead->setFirstName($data['first_name']);
        $lead->setLastName($data['last_name']);
        $lead->setPhoneNumber($data['phone_number']);
        $lead->setEmail($data['email'] ?? null);

        $em = $this->vicidialEm();
        $em->persist($lead);
        $em->flush();

        return $this->json([
            'message' => 'Lead créé avec succès',
            'id' => $lead->getId()
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /api/vicidial-leads/{id}
     */
    #[Route('/{id}', name: 'vicidial_lead_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $em = $this->vicidialEm();
        $lead = $em->getRepository(CrmLead::class)->find($id);

        if (!$lead) {
            return $this->json(['message' => 'Lead non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $lead->getId(),
            'firstName' => $lead->getFirstName(),
            'lastName' => $lead->getLastName(),
            'phoneNumber' => $lead->getPhoneNumber(),
            'email' => $lead->getEmail(),
        ], Response::HTTP_OK);
    }

    /**
     * PUT /api/vicidial-leads/{id}
     */
    #[Route('/{id}', name: 'vicidial_lead_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $em = $this->vicidialEm();
        $lead = $em->getRepository(CrmLead::class)->find($id);

        if (!$lead) {
            return $this->json(['message' => 'Lead non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $lead->setFirstName($data['first_name'] ?? $lead->getFirstName());
        $lead->setLastName($data['last_name'] ?? $lead->getLastName());
        $lead->setPhoneNumber($data['phone_number'] ?? $lead->getPhoneNumber());
        $lead->setEmail($data['email'] ?? $lead->getEmail());

        $em->flush();

        return $this->json(['message' => 'Lead mis à jour avec succès'], Response::HTTP_OK);
    }

    /**
     * DELETE /api/vicidial-leads/{id}
     */
    #[Route('/{id}', name: 'vicidial_lead_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $em = $this->vicidialEm();
        $lead = $em->getRepository(CrmLead::class)->find($id);

        if (!$lead) {
            return $this->json(['message' => 'Lead non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($lead);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}