<?php
namespace App\Controller\CampaignController;

use App\Service\Campaigns\CampaignService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CampaignController extends AbstractController
{
    private CampaignService $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    /**
     * @Route("/api/vicidial/campaigns", name="api_get_campaigns", methods={"GET"})
     */
    public function getCampaigns(): JsonResponse
    {
        try {
            $campaigns = $this->campaignService->getCampaigns();
            return new JsonResponse($campaigns, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to retrieve campaigns', 'details' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/api/vicidial/campaign/{campaignId}", name="api_get_campaign", methods={"GET"})
     */
    public function getCampaign(int $campaignId): JsonResponse
    {
        try {
            $campaign = $this->campaignService->getCampaignById($campaignId);
            return new JsonResponse($campaign, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to retrieve campaign', 'details' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }


/**
 * @Route("/api/vicidial/user-groups", name="api_get_user_groups", methods={"GET"})
 */
public function getUserGroups(): JsonResponse
{
    try {
        // Appeler le service pour récupérer les groupes d'utilisateurs
        $userGroups = $this->campaignService->getUserGroups();

        // Retourner les résultats sous forme de JSON
        return new JsonResponse($userGroups, JsonResponse::HTTP_OK);
    } catch (\Exception $e) {
        // En cas d'erreur, capturer et retourner une réponse d'erreur
        return new JsonResponse(
            ['error' => 'Failed to retrieve user groups', 'details' => $e->getMessage()],
            JsonResponse::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}


    /**
     * @Route("/api/vicidial/campaign", name="api_create_campaign", methods={"POST", "OPTIONS"})
     */
    public function addCampaign(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
    
        // Log les données reçues
        error_log('Données reçues pour la création de campagne: ' . json_encode($data));
    
        if (empty($data['campaign_id']) || empty($data['campaign_name'])) {
            error_log('Données manquantes: campaign_id ou campaign_name non fournis.');
            return new JsonResponse(['error' => 'Missing required fields'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        $result = $this->campaignService->createCampaign($data);
    
        if (isset($result['error'])) {
            error_log('Erreur lors de la création de campagne: ' . $result['error']);
            return new JsonResponse(['error' => $result['error']], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        return new JsonResponse(['success' => true, 'message' => 'Campaign created successfully'], JsonResponse::HTTP_CREATED);
    }
    
    /**
     * @Route("/api/vicidial/campaign/{campaignId}", name="api_delete_campaign", methods={"DELETE"})
     */
    public function deleteCampaign(string $campaignId): JsonResponse
    {
        return $this->campaignService->deleteCampaign($campaignId);
    }

    /**
     * @Route("/api/vicidial/campaign/{campaignId}", name="api_update_campaign", methods={"PUT"})
     */
    public function updateCampaign(string $campaignId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return $this->campaignService->updateCampaign($campaignId, $data);
    }
}
