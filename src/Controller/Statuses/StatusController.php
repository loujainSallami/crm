<?php

namespace App\Controller\Statuses;

use App\Service\Statuses\StatusesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class StatusController extends AbstractController
{
    public function __construct(
        private readonly StatusesService $statusesService
    ) {}

    #[Route('/api/vicidial/getAllStatuses', name: 'getAllStatuses', methods: ['GET'])]
    public function getAllStatuses(): JsonResponse
    {
        try {
            $statuses = $this->statusesService->getAllStatuses();
            return $this->json($statuses, JsonResponse::HTTP_OK);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Erreur lors de la récupération des statuts',
                'message' => $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/vicidial/status/{status}', name: 'api_get_status_info', methods: ['GET'])]
    public function getStatusInfo(string $status): JsonResponse
    {
        try {
            $result = $this->statusesService->getStatusInfo($status);
            return $this->json($result, JsonResponse::HTTP_OK);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Failed to get status info',
                'details' => $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/vicidial/get_statuses_by_campaign/{campaignId}', name: 'get_statuses_by_campaign', methods: ['GET'])]
    public function getStatusesByCampaign(string $campaignId): JsonResponse
    {
        try {
            $statuses = $this->statusesService->getStatusesByCampaign($campaignId);

            if (empty($statuses)) {
                return $this->json(['message' => 'Aucun statut trouvé pour cette campagne.'], JsonResponse::HTTP_NOT_FOUND);
            }

            return $this->json($statuses, JsonResponse::HTTP_OK);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Erreur lors de la récupération des statuts pour la campagne.',
                'details' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/vicidial/add-status', name: 'add_status', methods: ['POST'])]
    public function addStatus(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || empty($data['status']) || empty($data['status_name'])) {
            return $this->json(['error' => 'Missing required fields: status, status_name'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $this->statusesService->addStatus($data);
            return $this->json(['message' => 'Statut ajouté avec succès'], JsonResponse::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => "Erreur lors de l'ajout du statut",
                'details' => $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}