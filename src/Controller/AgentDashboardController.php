<?php

namespace App\Controller;

use App\Service\Agent\AgentPerformanceProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class AgentDashboardController extends AbstractController
{
    public function __construct(
        private readonly AgentPerformanceProvider $provider
    ) {}

    #[Route('/api/agents/performance', name: 'api_agents_performance', methods: ['GET'])]
    public function performance(): JsonResponse
    {
        try {
            $data = $this->provider->fetch();
            return $this->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}