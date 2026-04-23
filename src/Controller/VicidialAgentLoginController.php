<?php

namespace App\Controller;
use App\Service\Agent\VicidialAgentLoginService;
use App\Security\VicidialUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class VicidialAgentLoginController extends AbstractController
{
    #[Route('/api/vicidial/agent/login', name: 'api_vicidial_agent_login', methods: ['POST'])]
    public function login(
        Request $request,
        #[CurrentUser] ?VicidialUser $user,
        VicidialAgentLoginService $vicidialAgentLoginService
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        $phoneLogin = $data['phone_login'] ?? null;
        $phonePass = $data['phone_pass'] ?? null;
        $campaign = $data['campaign'] ?? null;

        if (!$phoneLogin || !$phonePass || !$campaign) {
            return new JsonResponse([
                'success' => false,
                'message' => 'phone_login, phone_pass et campaign sont obligatoires'
            ], 400);
        }

        $result = $vicidialAgentLoginService->loginAgent(
            $user->getUserIdentifier(),
            $phoneLogin,
            $phonePass,
            $campaign
        );

        return new JsonResponse($result, $result['success'] ? 200 : 400);
    }
}