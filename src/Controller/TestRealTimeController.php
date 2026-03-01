<?php
namespace App\Controller;

use App\Service\RealTimeProductionData\RealTimeProductionDataService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TestRealTimeController
{
    #[Route('/api/test/realtime', methods: ['GET'])]
    public function test(RealTimeProductionDataService $service): JsonResponse
    {
        $data = $service->fetchGroupsAndUsers();
        return new JsonResponse($data);
    }
}