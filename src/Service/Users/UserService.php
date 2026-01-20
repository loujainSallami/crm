<?php

namespace App\Service\Users;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserService
{
    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function getUsers(): JsonResponse
    {
        return new JsonResponse(['message' => 'Liste des utilisateurs']);
    }

    public function deleteUserDirectly(string $userId): JsonResponse
    {
        return new JsonResponse([
            'message' => "Utilisateur $userId supprimé avec succès"
        ]);
    }

    public function addUser(array $data): JsonResponse
    {
        if (empty($data['user'])) {
            return new JsonResponse(['error' => 'Identifiant utilisateur manquant'], 400);
        }

        return new JsonResponse([
            'message' => 'Utilisateur ajouté avec succès',
            'user' => $data['user']
        ], 201);
    }

    public function updateUser(string $user, array $data): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => $user
        ]);
    }

    public function generateUniqueUserId(): string
    {
        return 'USR_' . strtoupper(uniqid());
    }
}
