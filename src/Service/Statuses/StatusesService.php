<?php

namespace App\Service\Statuses;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class StatusesService
{
    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }


    /**
     * Récupère la liste de toutes les campagnes disponibles.
     *
     * @return array Tableau contenant les données des campagnes.
     * @throws \Exception En cas d'erreur de base de données.
     */
    public function getAllStatuses(): array
    {
        try {
            $query = "SELECT status , status_name  FROM vicidial_statuses";

            return $this->connection->fetchAllAssociative($query);
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la récupération des campagnes : " . $e->getMessage());
            throw new \Exception("Erreur lors de la récupération des campagnes : " . $e->getMessage());
        }
    }

    public function addStatus(array $data): void
{
    $query = "
        INSERT INTO vicidial_statuses (status, status_name, selectable, human_answered)
        VALUES (:status, :status_name, :selectable, :human_answered)
    ";

    $this->connection->executeQuery($query, [
        'status' => $data['status'],
        'status_name' => $data['status_name'],
        'selectable' => $data['selectable'] ?? 'Y',
        'human_answered' => $data['human_answered'] ?? 'N',
    ]);
}


public function getStatusesByCampaign(string $campaignId): array
{
    $sql = "SELECT * FROM vicidial_campaign_statuses WHERE campaign_id = :campaignId";

    try {
        return $this->connection->fetchAllAssociative($sql, ['campaignId' => $campaignId]);
    } catch (\Exception $e) {
        throw new \Exception('Erreur lors de la récupération des statuts : ' . $e->getMessage());
    }
}
    
}
