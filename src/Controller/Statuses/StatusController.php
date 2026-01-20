<?php
namespace App\Controller\Statuses;

use App\Service\Statuses\StatusesService; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class StatusController extends AbstractController
{
    private $statusesService;

    public function __construct(StatusesService $statusesService)
    {
        $this->statusesService = $statusesService;
    }



    /**
     * @Route("/api/vicidial/getAllStatuses", name="getAllStatuses", methods={"GET"})
     */
    public function getAllStatuses(): JsonResponse
    {
        try {
            $statuses = $this->statusesService->getAllStatuses();

            return new JsonResponse($statuses, 200);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la récupération des campagnes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @Route("/api/vicidial/status/{status}", name="api_get_status_info", methods={"GET"})
     */
    public function getStatusInfo(string $status): JsonResponse
    {
        try {
            $result = $this->statusesService->getStatusInfo($status);
            return new JsonResponse($result, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to get status info', 'details' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

/**
 * @Route("/api/vicidial/get_statuses_by_campaign/{campaignId}", name="get_statuses_by_campaign", methods={"GET"})
 */
public function getStatusesByCampaign(string $campaignId): JsonResponse
{
    try {
        // Appel au service pour récupérer les statuts par campagne
        $statuses = $this->statusesService->getStatusesByCampaign($campaignId);

        if (empty($statuses)) {
            return new JsonResponse(['message' => 'Aucun statut trouvé pour cette campagne.'], 404);
        }

        return new JsonResponse($statuses, JsonResponse::HTTP_OK);
    } catch (\Exception $e) {
        return new JsonResponse([
            'error' => 'Erreur lors de la récupération des statuts pour la campagne.',
            'details' => $e->getMessage(),
        ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }
}



/**
 * @Route("/api/vicidial/add-status", name="add_status", methods={"POST"})
 */
public function addStatus(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (!isset($data['status'], $data['status_name'])) {
        return new JsonResponse(['error' => 'Missing required fields'], 400);
    }

    try {
        $this->statusesService->addStatus($data);

        return new JsonResponse(['message' => 'Statut ajouté avec succès'], 201);
    } catch (\Exception $e) {
        return new JsonResponse(['error' => 'Erreur lors de l\'ajout du statut', 'details' => $e->getMessage()], 500);
    }
}

}