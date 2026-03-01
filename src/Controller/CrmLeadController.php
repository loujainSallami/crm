<?php

namespace App\Controller;

use App\Service\CrmLeadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/vicidial-leads')]
class CrmLeadController extends AbstractController
{
    public function __construct(private readonly CrmLeadService $crmLeadService) {}

    #[Route('', name: 'vicidial_leads_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $leads = $this->crmLeadService->getAllLeads();

        return $this->json($leads, Response::HTTP_OK);
    }

    #[Route('', name: 'vicidial_lead_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['phone_number'])) {
            return $this->json([
                'success' => false,
                'message' => 'first_name, last_name et phone_number sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $lead = $this->crmLeadService->createLead($data);

        return $this->json([
            'success' => true,
            'lead' => $lead
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'vicidial_lead_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $lead = $this->crmLeadService->getLeadById($id);

        if (!$lead) {
            return $this->json([
                'success' => false,
                'message' => 'Lead introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json($lead, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'vicidial_lead_update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $lead = $this->crmLeadService->updateLead($id, $data);

        if (!$lead) {
            return $this->json([
                'success' => false,
                'message' => 'Lead introuvable ou mise à jour échouée'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json($lead, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'vicidial_lead_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $deleted = $this->crmLeadService->deleteLead($id);

        if (!$deleted) {
            return $this->json([
                'success' => false,
                'message' => 'Lead introuvable ou suppression échouée'
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}