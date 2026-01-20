<?php

namespace App\Controller;

use App\Service\VicidialCampaignService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/campaigns')]
class VicidialCampaignController extends AbstractController
{
    public function __construct(private VicidialCampaignService $campaignService)
    {
    }

    #[Route('', name: 'campaign_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $campaigns = $this->campaignService->getAllCampaigns();

        return $this->json($campaigns, Response::HTTP_OK, [], ['groups' => 'campaign:read']);
    }

    #[Route('/{id}', name: 'campaign_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $campaign = $this->campaignService->getCampaignById($id);

        if (!$campaign) {
            return $this->json(['error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($campaign, Response::HTTP_OK, [], ['groups' => 'campaign:read']);
    }

    #[Route('', name: 'campaign_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        // Validation des champs requis
        if (empty($data['type']) || empty($data['campaign_name']) || !isset($data['active'])) {
            return $this->json(['error' => 'Missing required fields: type, campaign_name, active'], Response::HTTP_BAD_REQUEST);
        }
        // Créer la campagne avec les champs requis
        $campaign = $this->campaignService->createCampaign($data['type'], $data['campaign_name']);
        // Mettre à jour l'état actif de la campagne
        $campaign->setActive($data['active']); // Assurez-vous que 'active' est une chaîne
        // Persist the campaign if necessary
        $this->campaignService->updateCampaign($campaign); // Assurez-vous que cette méthode existe
        return $this->json($campaign, Response::HTTP_CREATED, [], ['groups' => 'campaign:read']);
    }
    

    #[Route('/{id}', name: 'campaign_update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $campaign = $this->campaignService->getCampaignById($id);

        if (!$campaign) {
            return $this->json(['error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Validation simple (à améliorer selon vos besoins)
        if (empty($data['type']) || empty($data['campaign_name']) || !isset($data['active'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $campaign->setType($data['type']);
        $campaign->setCampaignName($data['campaign_name']);
        $campaign->setActive((bool)$data['active']);
        $campaign->setDialStatusA($data['dial_status_a'] ?? null);
        $campaign->setWebFormAddress($data['web_form_address'] ?? null);
        $campaign->setScheduledCallbacks($data['scheduled_callbacks'] ?? null);

        $this->campaignService->updateCampaign($campaign);

        return $this->json($campaign, Response::HTTP_OK, [], ['groups' => 'campaign:read']);
    }

    #[Route('/{id}', name: 'campaign_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $campaign = $this->campaignService->getCampaignById($id);

        if (!$campaign) {
            return $this->json(['error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);
        }

        $this->campaignService->deleteCampaign($campaign);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
