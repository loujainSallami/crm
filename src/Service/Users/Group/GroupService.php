<?php

namespace App\Service\Users\Group;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class GroupService
{
    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    private function isCampaignValid(string $campaignId): bool
    {
        $query = "SELECT COUNT(*) FROM vicidial_campaigns WHERE campaign_id = :campaign_id";
        $count = $this->connection->fetchOne($query, [
            'campaign_id' => $campaignId
        ]);

        return (int) $count > 0;
    }

    private function groupExists(string $userGroup): bool
    {
        $query = "SELECT COUNT(*) FROM vicidial_user_groups WHERE user_group = :user_group";
        $count = $this->connection->fetchOne($query, [
            'user_group' => $userGroup
        ]);

        return (int) $count > 0;
    }

    public function addGroup(array $data): JsonResponse
    {
        try {
            $allowedCampaigns = $data['allowed_campaigns'] ?? '-ALL-CAMPAIGNS- - -';

            if (
                $allowedCampaigns !== '-ALL-CAMPAIGNS- - -' &&
                !$this->isCampaignValid($allowedCampaigns)
            ) {
                return new JsonResponse([
                    'error' => 'La campagne spécifiée n\'existe pas.'
                ], 400);
            }

            if ($this->groupExists($data['user_group'])) {
                return new JsonResponse([
                    'error' => 'Le groupe existe déjà.'
                ], 409);
            }

            $query = "
                INSERT INTO vicidial_user_groups (
                    user_group,
                    group_name,
                    allowed_campaigns
                )
                VALUES (
                    :user_group,
                    :group_name,
                    :allowed_campaigns
                )
            ";

            $this->connection->executeStatement($query, [
                'user_group' => $data['user_group'],
                'group_name' => $data['group_name'],
                'allowed_campaigns' => $allowedCampaigns
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Groupe créé avec succès.'
            ], 201);
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de l'ajout du groupe : " . $e->getMessage());

            return new JsonResponse([
                'error' => 'Erreur lors de la création du groupe.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateGroup(string $userGroup, array $data): JsonResponse
    {
        try {
            if (!$this->groupExists($userGroup)) {
                return new JsonResponse([
                    'error' => 'Le groupe spécifié n\'existe pas.'
                ], 404);
            }

            if (
                $data['allowed_campaigns'] !== '-ALL-CAMPAIGNS- - -' &&
                !$this->isCampaignValid($data['allowed_campaigns'])
            ) {
                return new JsonResponse([
                    'error' => 'La campagne spécifiée n\'existe pas.'
                ], 400);
            }

            $query = "
                UPDATE vicidial_user_groups
                SET
                    group_name = :group_name,
                    allowed_campaigns = :allowed_campaigns
                WHERE user_group = :user_group
            ";

            $this->connection->executeStatement($query, [
                'user_group' => $userGroup,
                'group_name' => $data['group_name'],
                'allowed_campaigns' => $data['allowed_campaigns'],
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Groupe mis à jour avec succès.'
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la mise à jour du groupe : " . $e->getMessage());

            return new JsonResponse([
                'error' => 'Erreur lors de la mise à jour du groupe.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteGroup(string $userGroup): JsonResponse
    {
        try {
            if (!$this->groupExists($userGroup)) {
                return new JsonResponse([
                    'error' => 'Le groupe spécifié n\'existe pas.'
                ], 404);
            }

            $queryDelete = "
                DELETE FROM vicidial_user_groups
                WHERE user_group = :user_group
            ";

            $this->connection->executeStatement($queryDelete, [
                'user_group' => $userGroup
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Groupe supprimé avec succès.'
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la suppression du groupe : " . $e->getMessage());

            return new JsonResponse([
                'error' => 'Erreur lors de la suppression du groupe.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getCampaigns(): JsonResponse
    {
        try {
            $query = "
                SELECT campaign_id, campaign_name
                FROM vicidial_campaigns
                ORDER BY campaign_id ASC
            ";

            $campaigns = $this->connection->fetchAllAssociative($query);

            return new JsonResponse([
                'success' => true,
                'data' => $campaigns
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la récupération des campagnes : " . $e->getMessage());

            return new JsonResponse([
                'error' => 'Erreur lors de la récupération des campagnes.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getGroups(): JsonResponse
    {
        try {
            $query = "
                SELECT
                    user_group,
                    group_name,
                    allowed_campaigns
                FROM vicidial_user_groups
                ORDER BY user_group ASC
            ";

            $groups = $this->connection->fetchAllAssociative($query);

            return new JsonResponse([
                'success' => true,
                'data' => $groups
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la récupération des groupes : " . $e->getMessage());

            return new JsonResponse([
                'error' => 'Erreur lors de la récupération des groupes.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getUserGroupsOnly(): array
    {
        try {
            $query = "
                SELECT DISTINCT user_group
                FROM vicidial_users
                WHERE user_group IS NOT NULL
                  AND user_group <> ''
                ORDER BY user_group
            ";

            return $this->connection->fetchFirstColumn($query);
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la récupération des user_groups : " . $e->getMessage());

            throw new \Exception(
                "Erreur lors de la récupération des user_groups : " . $e->getMessage()
            );
        }
    }
}