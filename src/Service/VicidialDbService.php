<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class VicidialDbService
{
    public function __construct(
        private readonly Connection $vicidialConnection
    ) {
    }

    /**
     * Crée une campagne dans Vicidial
     */
    public function createCampaign(array $data): array
    {
        $sql = "
            INSERT INTO vicidial_campaigns (
                campaign_id,
                campaign_name,
                campaign_description,
                active,
                lead_order,
                cache_level,
                dial_method,
                dial_level,
                lead_recycle,
                web_lead_recycle,
                script_id,
                ring_timeout,
                available_only_ratio,
                filter_type
            ) VALUES (
                :campaign_id,
                :campaign_name,
                :campaign_description,
                :active,
                :lead_order,
                :cache_level,
                :dial_method,
                :dial_level,
                :lead_recycle,
                :web_lead_recycle,
                :script_id,
                :ring_timeout,
                :available_only_ratio,
                :filter_type
            )
        ";

        $params = [
            'campaign_id' => $data['campaign_id'] ?? null,
            'campaign_name' => $data['campaign_name'] ?? null,
            'campaign_description' => $data['campaign_description'] ?? '',
            'active' => $data['active'] ?? 'N',
            'lead_order' => $data['lead_order'] ?? 'ASC',
            'cache_level' => $data['cache_level'] ?? 10,
            'dial_method' => $data['dial_method'] ?? 'RATIO',
            'dial_level' => $data['dial_level'] ?? 1.0,
            'lead_recycle' => !empty($data['lead_recycle']) ? 'Y' : 'N',
            'web_lead_recycle' => !empty($data['web_lead_recycle']) ? 'Y' : 'N',
            'script_id' => $data['script_id'] ?? null,
            'ring_timeout' => $data['ring_timeout'] ?? 20,
            'available_only_ratio' => $data['available_only_ratio'] ?? 1.2,
            'filter_type' => $data['filter_type'] ?? 'standard',
        ];

        try {
            $this->vicidialConnection->executeStatement($sql, $params);

            return [
                'success' => true,
                'message' => 'Campaign created successfully',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create campaign',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function userExists(string $username): bool
    {
        $count = $this->vicidialConnection->fetchOne(
            'SELECT COUNT(*) FROM vicidial_users WHERE user = ?',
            [$username]
        );

        return (int) $count > 0;
    }

    public function campaignExists(string $campaignId): bool
    {
        $count = $this->vicidialConnection->fetchOne(
            'SELECT COUNT(*) FROM vicidial_campaigns WHERE campaign_id = ?',
            [$campaignId]
        );

        return (int) $count > 0;
    }

    public function leadExists(int $leadId): bool
    {
        $count = $this->vicidialConnection->fetchOne(
            'SELECT COUNT(*) FROM vicidial_list WHERE lead_id = ?',
            [$leadId]
        );

        return (int) $count > 0;
    }

    public function getUserByUsername(string $username): ?array
    {
        $row = $this->vicidialConnection->fetchAssociative(
            '
            SELECT
                user,
                full_name,
                user_group,
                user_level,
                active,
                phone_login
            FROM vicidial_users
            WHERE user = ?
            ',
            [$username]
        );

        return $row ?: null;
    }

    public function getCampaignById(string $campaignId): ?array
    {
        $row = $this->vicidialConnection->fetchAssociative(
            '
            SELECT
                campaign_id,
                campaign_name,
                campaign_description,
                active,
                dial_method
            FROM vicidial_campaigns
            WHERE campaign_id = ?
            ',
            [$campaignId]
        );
    
        return $row ?: null;
    }

    public function getLeadById(int $leadId): ?array
    {
        $row = $this->vicidialConnection->fetchAssociative(
            '
            SELECT
                lead_id,
                first_name,
                last_name,
                phone_number,
                email,
                status,
                list_id
            FROM vicidial_list
            WHERE lead_id = ?
            ',
            [$leadId]
        );

        return $row ?: null;
    }
}