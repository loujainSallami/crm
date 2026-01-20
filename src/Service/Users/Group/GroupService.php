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
        $count = $this->connection->fetchOne($query, ['campaign_id' => $campaignId]);
    
        return $count > 0;
    }
    
    


    public function addGroup(array $data): JsonResponse
    {
        try {
            // Par défaut, associer le groupe à "ALL-CAMPAIGNS" si aucune campagne n'est spécifiée
            $allowedCampaigns = $data['campaign_id'] ?? '-ALL-CAMPAIGNS- - -';
    
            // Vérifiez si la campagne existe (sauf si c'est "ALL-CAMPAIGNS")
            if ($allowedCampaigns !== '-ALL-CAMPAIGNS- - -' && !$this->isCampaignValid($allowedCampaigns)) {
                return new JsonResponse(['error' => 'La campagne spécifiée n\'existe pas.'], 400);
            }
    
            // Insérez le groupe dans la table vicidial_user_groups
            $query = "
                INSERT INTO vicidial_user_groups (user_group, group_name, allowed_campaigns)
                VALUES (:user_group, :group_name, :allowed_campaigns)
            ";
            $this->connection->executeStatement($query, [
                'user_group' => $data['user_group'],
                'group_name' => $data['group_name'],
                'allowed_campaigns' => $allowedCampaigns
            ]);
    
            return new JsonResponse(['message' => 'Groupe créé avec succès et associé aux campagnes spécifiées.'], 201);
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de l'ajout du groupe : " . $e->getMessage());
            return new JsonResponse(['error' => 'Erreur lors de la création du groupe.', 'message' => $e->getMessage()], 500);
        }
    }
    

    private function associateGroupWithCampaign(string $userGroup, string $campaignId): void
{
    try {
        $query = "
            INSERT INTO vicidial_group_campaigns (user_group, campaign_id)
            VALUES (:user_group, :campaign_id)
            ON DUPLICATE KEY UPDATE campaign_id = :campaign_id
        ";
        $this->connection->executeStatement($query, [
            'user_group' => $userGroup,
            'campaign_id' => $campaignId
        ]);
    } catch (\Exception $e) {
        $this->logger->error("Erreur lors de l'association du groupe avec la campagne : " . $e->getMessage());
    }
}

    





public function updateGroup(string $userGroup, array $data): JsonResponse
{
    try {
        $query = "
            UPDATE vicidial_user_groups
            SET group_name = :group_name, allowed_campaigns = :allowed_campaigns
            WHERE user_group = :user_group
        ";

        $this->connection->executeStatement($query, [
            'user_group' => $userGroup,
            'group_name' => $data['group_name'], // Nom du groupe
            'allowed_campaigns' => $data['allowed_campaigns'], // ID de la campagne sélectionnée
        ]);

        return new JsonResponse(['message' => 'Groupe mis à jour avec succès.'], 200);
    } catch (\Exception $e) {
        $this->logger->error("Erreur lors de la mise à jour du groupe : " . $e->getMessage());
        return new JsonResponse(['error' => 'Erreur lors de la mise à jour du groupe.', 'message' => $e->getMessage()], 500);
    }
}

    





public function deleteGroup(string $userGroup): JsonResponse
{
    try {
        // Vérifiez si le groupe existe
        $queryCheck = "SELECT COUNT(*) FROM vicidial_user_groups WHERE user_group = :user_group";
        $count = $this->connection->fetchOne($queryCheck, ['user_group' => $userGroup]);

        if ($count == 0) {
            return new JsonResponse(['error' => 'Le groupe spécifié n\'existe pas.'], 404);
        }

        // Supprimez le groupe
        $queryDelete = "DELETE FROM vicidial_user_groups WHERE user_group = :user_group";
        $this->connection->executeStatement($queryDelete, ['user_group' => $userGroup]);

        return new JsonResponse(['message' => 'Groupe supprimé avec succès.'], 200);
    } catch (\Exception $e) {
        $this->logger->error("Erreur lors de la suppression du groupe : " . $e->getMessage());
        return new JsonResponse(['error' => 'Erreur lors de la suppression du groupe.', 'message' => $e->getMessage()], 500);
    }
}

public function getCampaigns(): JsonResponse
{
    try {
        $query = "SELECT campaign_id, campaign_name FROM vicidial_campaigns";
        $campaigns = $this->connection->fetchAllAssociative($query);

        return new JsonResponse($campaigns, 200);
    } catch (\Exception $e) {
        $this->logger->error("Erreur lors de la récupération des campagnes : " . $e->getMessage());
        return new JsonResponse(['error' => 'Erreur lors de la récupération des campagnes.', 'message' => $e->getMessage()], 500);
    }
}



    public function getGroups(): JsonResponse
    {
        try {
            $query = "SELECT + user_group ,	group_name 	, allowed_campaigns FROM vicidial_user_groups";
            $groups = $this->connection->fetchAllAssociative($query);

            return new JsonResponse($groups, 200);
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la récupération des groupes : " . $e->getMessage());
            return new JsonResponse(['error' => 'Erreur lors de la récupération des groupes.', 'message' => $e->getMessage()], 500);
        }
    }


    public function getUserGroupsOnly(): array
    {
        try {
            $query = "SELECT DISTINCT user_group FROM vicidial_user_groups";
            return $this->connection->fetchFirstColumn($query);
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la récupération des user_groups : " . $e->getMessage());
            throw new \Exception("Erreur lors de la récupération des user_groups : " . $e->getMessage());
        }
    }
}
