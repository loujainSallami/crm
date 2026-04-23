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
        try {
            $sql = "
            SELECT 
                u.user_id,
                u.user,
                u.full_name,
                u.user_group,
                g.group_name,
                u.user_level,
                u.phone_login,
                u.phone_pass,
                u.active
            FROM vicidial_users u
            LEFT JOIN vicidial_user_groups g 
                ON u.user_group = g.user_group
            ORDER BY u.user_id DESC
        ";
            $users = $this->connection->fetchAllAssociative($sql);

            return new JsonResponse($users, 200);
        } catch (\Exception $e) {
            $this->logger->error('Erreur getUsers: ' . $e->getMessage());

            return new JsonResponse([
                'error' => 'Erreur lors de la récupération des utilisateurs'
            ], 500);
        }
    }

    public function deleteUserDirectly(string $userId): JsonResponse
    {
        try {
            $deletedRows = $this->connection->delete('vicidial_users', [
                'user' => $userId
            ]);

            if ($deletedRows === 0) {
                return new JsonResponse([
                    'error' => "Aucun utilisateur trouvé avec l'identifiant $userId"
                ], 404);
            }

            return new JsonResponse([
                'message' => "Utilisateur $userId supprimé avec succès"
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error('Erreur deleteUserDirectly: ' . $e->getMessage());

            return new JsonResponse([
                'error' => "Erreur lors de la suppression de l'utilisateur"
            ], 500);
        }
    }

    public function addUser(array $data): JsonResponse
    {
        try {
            if (empty($data['user'])) {
                return new JsonResponse(['error' => 'Identifiant utilisateur manquant'], 400);
            }

            $exists = $this->connection->fetchOne(
                "SELECT COUNT(*) FROM vicidial_users WHERE user = ?",
                [$data['user']]
            );

            if ($exists > 0) {
                return new JsonResponse([
                    'error' => 'Cet utilisateur existe déjà'
                ], 409);
            }

            $this->connection->insert('vicidial_users', [
                'user' => $data['user'],
                'full_name' => $data['full_name'] ?? '',
                'pass' => $data['pass'] ?? '',
                'user_group' => $data['user_group'] ?? '',
                'user_level' => $data['user_level'] ?? 1,
                'phone_login' => $data['phone_login'] ?? 0,
                'phone_pass' => $data['phone_pass'] ?? 0,
            ]);

            return new JsonResponse([
                'message' => 'Utilisateur ajouté avec succès',
                'user' => $data['user']
            ], 201);
        } catch (\Exception $e) {
            $this->logger->error('Erreur addUser: ' . $e->getMessage());

            return new JsonResponse([
                'error' => "Erreur lors de l'ajout de l'utilisateur"
            ], 500);
        }
    }

    public function updateUser(string $user, array $data): JsonResponse
    {
        try {
            $updatedRows = $this->connection->update(
                'vicidial_users',
                [
                    'full_name' => $data['full_name'] ?? '',
                    'pass' => $data['pass'] ?? '',
                    'user_group' => $data['user_group'] ?? '',
                    'user_level' => $data['user_level'] ?? 1,
                    'phone_login' => $data['phone_login'] ?? 0,
                    'phone_pass' => $data['phone_pass'] ?? 0,
                ],
                ['user' => $user]
            );

            if ($updatedRows === 0) {
                return new JsonResponse([
                    'error' => "Aucun utilisateur trouvé avec l'identifiant $user"
                ], 404);
            }

            return new JsonResponse([
                'message' => 'Utilisateur mis à jour avec succès',
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error('Erreur updateUser: ' . $e->getMessage());

            return new JsonResponse([
                'error' => "Erreur lors de la mise à jour de l'utilisateur"
            ], 500);
        }
    }

    public function generateUniqueUserId(): string
    {
        return 'USR_' . strtoupper(uniqid());
    }
}