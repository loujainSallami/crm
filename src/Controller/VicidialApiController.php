<?php

namespace App\Controller;

use App\Service\VicidialApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class VicidialApiController extends AbstractController
{
    public function __construct(
        private readonly VicidialApiService $vicidialApiService
    ) {}

    private function vicidialUnavailable(\Throwable $e): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => 'VICIDIAL_UNREACHABLE',
            'message' => 'Le serveur Vicidial est indisponible',
            'details' => $e->getMessage(),
        ], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
    }

    #[Route('/api/vicidial/getVersion', name: 'get_version', methods: ['GET'])]
    public function getVersion(): JsonResponse
    {
        try {
            $user = $this->getUser();
            $data = $this->vicidialApiService->getVersion();

            return new JsonResponse([
                'success' => true,
                'data' => $data,
                'user' => $user ? $user->getUserIdentifier() : null,
            ]);
        } catch (\Throwable $e) {
            return $this->vicidialUnavailable($e);
        }
    }

    #[Route('/api/vicidial/getCampaigns', name: 'get_campaigns', methods: ['GET'])]
    public function getCampaigns(): JsonResponse
    {
        try {
            $campaigns = $this->vicidialApiService->getCampaigns();

            return new JsonResponse([
                'success' => true,
                'data' => $campaigns
            ]);
        } catch (\Throwable $e) {
            return $this->vicidialUnavailable($e);
        }
    }

    #[Route('/api/vicidial/addCampaign', name: 'add_campaign', methods: ['POST'])]
    public function addCampaign(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        if (empty($data['campaign_name']) || empty($data['campaign_description'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'campaign_name et campaign_description sont obligatoires',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->vicidialApiService->createCampaign($data);

            return new JsonResponse([
                'success' => true,
                'message' => 'Campaign created successfully',
                'data' => $result,
            ], JsonResponse::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->vicidialUnavailable($e);
        }
    }

    #[Route('/api/vicidial/getUsers', name: 'get_users', methods: ['GET'])]
    public function getUsers(): JsonResponse
    {
        try {
            $users = $this->vicidialApiService->getUsers();

            return new JsonResponse([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Throwable $e) {
            return $this->vicidialUnavailable($e);
        }
    }

    // ✅ NOUVELLE ROUTE LEADS
    #[Route('/api/vicidial/getLeads', name: 'get_leads', methods: ['GET'])]
    public function getLeads(): JsonResponse
    {
        try {
            $leads = $this->vicidialApiService->getLeads();

            return new JsonResponse([
                'success' => true,
                'data' => $leads
            ]);
        } catch (\Throwable $e) {
            return $this->vicidialUnavailable($e);
        }
    }
}