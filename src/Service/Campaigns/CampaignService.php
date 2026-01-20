<?php
namespace App\Service\Campaigns;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse; 
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Doctrine\DBAL\Connection;

use Doctrine\ORM\EntityManagerInterface;

class CampaignService
{
    private HttpClientInterface $client;
    private Connection $connection;
    private string $apiUrl;
    private string $apiUser;
    private string $apiPass;
    private string $source;
    private EntityManagerInterface $entityManager;

    public function __construct(
        HttpClientInterface $client,
        Connection $connection,
        string $apiUrl,
        string $apiUser,
        string $apiPass,
        string $source,
        EntityManagerInterface $entityManager
    ) 
    {
        $this->client = $client;
        $this->connection = $connection;
        $this->apiUrl = $apiUrl;
        $this->apiUser = $apiUser;
        $this->apiPass = $apiPass;
        $this->source = $source;
        $this->entityManager = $entityManager;
    }

    public function getCampaigns(): array
    {
        $response = $this->client->request('GET', $this->apiUrl, [
            'query' => [
                'source' => $this->source,
                'user' => $this->apiUser,
                'pass' => $this->apiPass,
                'function' => 'campaigns_list',
            ],
        ]);
    
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to retrieve campaigns');
        }
    
        $data = $response->getContent();
    
        return array_filter(array_map(function ($line) {
            $fields = explode('|', $line);
    
            // Assurez-vous que le tableau a assez de champs
            return count($fields) >= 10
                ? [
                    'campaign_id' => $fields[0],
                    'campaign_name' => $fields[1],
                    'active' => $fields[2],
                    'dial_method' => $fields[3],
                    'dial_ratio' => $fields[4],
                    'leads_not_called' => $fields[5],
                    'leads_called' => $fields[6],
                    'leads_to_call' => $fields[7],
                    'total_leads' => $fields[8],
                    'list_count' => $fields[9],
                ]
                : null;
        }, explode("\n", trim($data))));
    }
    

    public function getCampaignById(int $campaignId): array
    {
        $response = $this->client->request('GET', $this->apiUrl, [
            'query' => [
                'source' => $this->source,
                'user' => $this->apiUser,
                'pass' => $this->apiPass,
                'function' => 'get_campaign',
                'campaign_id' => $campaignId,
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to retrieve campaign');
        }

        return $response->toArray();
    }


    public function getUserGroups(): array
{
    try {
        $query = "SELECT  user_group , group_name FROM vicidial_user_groups";
        $result = $this->connection->fetchAllAssociative($query);

        return $result;
    } catch (\Exception $e) {
        error_log('Erreur lors de la récupération des user groups : ' . $e->getMessage());
        return ['error' => 'Failed to fetch user groups', 'message' => $e->getMessage()];
    }
}


public function createCampaign(array $data): array
{
    try {
        error_log('Tentative de création de campagne avec les données: ' . json_encode($data));

        // Valider les champs requis
        $requiredFields = ['campaign_id', 'campaign_name', 'active', 'dial_statuses', 'user_group'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return ['error' => "Field '{$field}' is required but missing."];
            }
        }

        // Préparer la requête SQL
        $query = "
            INSERT INTO vicidial_campaigns (
                campaign_id,
                campaign_name,
                campaign_description,
                active,
                dial_statuses,
                user_group
            ) VALUES (
                :campaign_id,
                :campaign_name,
                :campaign_description,
                :active,
                :dial_statuses,
                :user_group
            )
        ";

        // Exécuter la requête
        $this->connection->executeStatement($query, [
            'campaign_id' => $data['campaign_id'],
            'campaign_name' => $data['campaign_name'],
            'campaign_description' => $data['campaign_description'] ?? '',
            'active' => $data['active'] ?? 'N',
            'dial_statuses' => $data['dial_statuses'],
            'user_group' => $data['user_group'], // Associer avec le user group
        ]);

        error_log('Campagne créée avec succès : ' . $data['campaign_id']);

        return [
            'success' => true,
            'message' => 'Campaign created successfully',
            'campaign_id' => $data['campaign_id'],
        ];
    } catch (\Exception $e) {
        error_log('Erreur SQL : ' . $e->getMessage());
        return ['error' => 'Failed to create campaign', 'message' => $e->getMessage()];
    }
}


        
/**
 * Supprime une campagne dans la base de données.
 */
public function deleteCampaign(string $campaignId): JsonResponse
{
    try {
        $query = "DELETE FROM vicidial_campaigns WHERE campaign_id = :campaignId";
        $this->entityManager->getConnection()->executeStatement($query, ['campaignId' => $campaignId]);

        return new JsonResponse(['success' => true, 'message' => 'Campaign deleted successfully'], JsonResponse::HTTP_OK);
    } catch (\Exception $e) {
        return new JsonResponse(['success' => false, 'message' => 'Failed to delete campaign: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }
}


/**
 * Met à jour une campagne.
 */
public function updateCampaign(string $campaignId, array $data): JsonResponse
{
    $response = $this->client->request('POST', $this->apiUrl, [
        'query' => [
            'source' => $this->source,
            'user' => $this->apiUser,
            'pass' => $this->apiPass,
            'function' => 'update_campaign',
            'campaign_id' => $campaignId,
            'campaign_name' => $data['campaign_name'] ?? '',
            'campaign_description' => $data['campaign_description'] ?? '',
            'active' => $data['active'] ?? 'N',
            'dial_method' => $data['dial_method'] ?? 'RATIO',
            'call_ratio' => $data['call_ratio'] ?? '1.0',
            'lead_order' => $data['lead_order'] ?? 'ASC',
            'cache_level' => $data['cache_level'] ?? '10',
            'lead_recycle' => $data['lead_recycle'] ?? 'N',
            'web_lead_recycle' => $data['web_lead_recycle'] ?? 'N',
            'script_id' => $data['script_id'] ?? '',
            'ring_timeout' => $data['ring_timeout'] ?? '20',
            'available_ratio' => $data['available_ratio'] ?? '1.2',
            'filter_type' => $data['filter_type'] ?? 'standard',
        ],
    ]);

    if ($response->getStatusCode() === 200) {
        $responseContent = $response->getContent(false);
        if (str_contains($responseContent, 'SUCCESS')) {
            return new JsonResponse(['success' => true, 'message' => 'Campaign updated successfully'], JsonResponse::HTTP_OK);
        } else {
            return new JsonResponse(['success' => false, 'message' => 'Failed to update campaign: ' . $responseContent], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    return new JsonResponse(['success' => false, 'message' => 'Error during update request'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
}

}
