<?php

namespace App\Service\Statuses;

use Doctrine\DBAL\Connection;

class StatusesService
{
    public function __construct(
        private readonly Connection $vicidialConnection
    ) {}

    // 🔹 Récupérer tous les statuts
    public function getAllStatuses(): array
    {
        return $this->vicidialConnection->fetchAllAssociative(
            "SELECT status, status_name, selectable FROM vicidial_statuses ORDER BY status ASC"
        );
    }

    // 🔹 Récupérer un statut spécifique
    public function getStatusInfo(string $status): ?array
    {
        return $this->vicidialConnection->fetchAssociative(
            "SELECT * FROM vicidial_statuses WHERE status = ?",
            [$status]
        );
    }

    // 🔹 Récupérer les statuts par campagne
    public function getStatusesByCampaign(string $campaignId): array
    {
        return $this->vicidialConnection->fetchAllAssociative(
            "SELECT status, status_name 
             FROM vicidial_campaign_statuses 
             WHERE campaign_id = ?",
            [$campaignId]
        );
    }

    // 🔹 Ajouter un nouveau statut
    public function addStatus(array $data): void
    {
        $this->vicidialConnection->insert(
            'vicidial_statuses',
            [
                'status' => $data['status'],
                'status_name' => $data['status_name'],
                'selectable' => $data['selectable'] ?? 'Y',
            ]
        );
    }
}