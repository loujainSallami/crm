<?php

namespace App\Controller\UsersController;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Users\UserService;

class UserController extends AbstractController
{
    private LoggerInterface $logger;
    private UserService $userService;

    public function __construct(UserService $userService, LoggerInterface $logger)
    {
        $this->userService = $userService;
        $this->logger = $logger;
    }

    /**
     * @Route("/api/vicidial/users", name="api_get_users", methods={"GET"})
     */
    public function getUsers(): JsonResponse
    {
        $this->logger->info("Appel de la méthode getUsers dans UserController.");

        return $this->userService->getUsers();
    }

    /**
     * @Route("/api/vicidial/user/{userId}/delete-direct", name="api_delete_user_direct", methods={"DELETE"})
     */
    public function deleteUserDirectly(string $userId): JsonResponse
    {
        return $this->userService->deleteUserDirectly($userId);
    }

    /**
     * @Route("/api/vicidial/user", name="api_add_user", methods={"POST"})
     */
    public function addUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'JSON invalide'], 400);
        }

        // Générer automatiquement un identifiant si absent
        if (empty($data['user'])) {
            $data['user'] = $this->userService->generateUniqueUserId();
        }

        return $this->userService->addUser($data);
    }

    /**
     * @Route("/api/vicidial/user/{user}", name="api_update_user", methods={"PUT"})
     */
    public function updateUser(Request $request, string $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'JSON invalide'], 400);
        }

        return $this->userService->updateUser($user, $data);
    }
}
