<?php

namespace App\Controller;

use App\Service\VicidialAgentApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/vicidial/agent')]
class VicidialAgentController extends AbstractController
{
    public function __construct(
        private readonly VicidialAgentApiService $service
    ) {}

    private function getJsonData(Request $request): array
    {
        $content = trim($request->getContent());

        if ($content === '') {
            return [];
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }

    private function getAuthenticatedAgentUser(): string
    {
        $user = $this->getUser();
    
        if (!$user) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié.');
        }
    
        if (method_exists($user, 'getUserIdentifier')) {
            return $user->getUserIdentifier();
        }
    
        if (method_exists($user, 'getUsername')) {
            return $user->getUsername();
        }
    
        throw new \LogicException('Impossible de récupérer l’identifiant utilisateur.');
    }
    #[Route('/dashboard', name: 'vicidial_agent_dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        $agentUser = $this->getAuthenticatedAgentUser(); // ✅ défini ici
    
        $data = $this->service->getAgentDashboard($agentUser); // ✅ utilisé après
    
        dump($agentUser, $data); // debug utile
    
        return $this->json($data);
    }
    #[Route('/status', name: 'vicidial_agent_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        $agentUser = $this->getAuthenticatedAgentUser();

        return $this->json($this->service->agentStatus($agentUser));
    }

    #[Route('/logout', name: 'vicidial_agent_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $agentUser = $this->getAuthenticatedAgentUser();

        return $this->json($this->service->logout($agentUser));
    }

    #[Route('/pause', name: 'vicidial_agent_pause', methods: ['POST'])]
    public function pause(Request $request): JsonResponse
    {
        $agentUser = $this->getAuthenticatedAgentUser();
        $data = $this->getJsonData($request);
        $pauseCode = isset($data['pauseCode']) && is_string($data['pauseCode']) && trim($data['pauseCode']) !== ''
            ? trim($data['pauseCode'])
            : 'PAUSE';

        return $this->json($this->service->pause($agentUser, $pauseCode));
    }

    #[Route('/resume', name: 'vicidial_agent_resume', methods: ['POST'])]
    public function resume(): JsonResponse
    {
        $agentUser = $this->getAuthenticatedAgentUser();

        return $this->json($this->service->resume($agentUser));
    }

    #[Route('/call', name: 'vicidial_agent_call', methods: ['POST'])]
    public function call(Request $request): JsonResponse
    {
        $agentUser = $this->getAuthenticatedAgentUser();
        $data = $this->getJsonData($request);

        $phone = isset($data['phone']) && is_string($data['phone']) ? trim($data['phone']) : null;
        $campaign = isset($data['campaign']) && is_string($data['campaign']) && trim($data['campaign']) !== ''
            ? trim($data['campaign'])
            : 'TEST123';

        if (!$phone) {
            return $this->json([
                'success' => false,
                'message' => 'phone is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(
            $this->service->externalDial($agentUser, $phone, $campaign)
        );
    }

    #[Route('/hangup', name: 'vicidial_agent_hangup', methods: ['POST'])]
    public function hangup(): JsonResponse
    {
        $agentUser = $this->getAuthenticatedAgentUser();

        return $this->json($this->service->hangup($agentUser));
    }

    #[Route('/transfer', name: 'vicidial_agent_transfer', methods: ['POST'])]
    public function transfer(Request $request): JsonResponse
    {
        $agentUser = $this->getAuthenticatedAgentUser();
        $data = $this->getJsonData($request);

        $extension = isset($data['extension']) && is_string($data['extension'])
            ? trim($data['extension'])
            : null;

        if (!$extension) {
            return $this->json([
                'success' => false,
                'message' => 'extension is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(
            $this->service->transfer($agentUser, $extension)
        );
    }

    #[Route('/next-call', name: 'vicidial_agent_next_call', methods: ['GET'])]
    public function nextCall(): JsonResponse
    {
        $agentUser = $this->getAuthenticatedAgentUser();

        return $this->json($this->service->getNextCall($agentUser));
    }

    #[Route('/dispo', name: 'vicidial_agent_dispo', methods: ['POST'])]
    public function dispo(Request $request): JsonResponse
    {
        $agentUser = $this->getAuthenticatedAgentUser();
        $data = $this->getJsonData($request);

        $status = isset($data['status']) && is_string($data['status'])
            ? trim($data['status'])
            : null;

        if (!$status) {
            return $this->json([
                'success' => false,
                'message' => 'status is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(
            $this->service->setDisposition($agentUser, $status)
        );
    }

    #[Route('/debug-connections', name: 'vicidial_agent_debug_connections', methods: ['GET'])]
    public function debugConnections(): JsonResponse
    {
        $agentUser = null;

        try {
            $agentUser = $this->getAuthenticatedAgentUser();
        } catch (\Throwable) {
        }

        return $this->json([
            'authenticated_agent_user' => $agentUser,
            'debug' => $this->service->debugConnections(),
        ]);
    }
}