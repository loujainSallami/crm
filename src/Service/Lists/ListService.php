<?php

namespace App\Service\Lists;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\DBAL\Connection;

class ListService
{
    private HttpClientInterface $client;
    private string $apiUrl;
    private string $source;
    private string $apiUser;
    private string $apiPass;
    private Connection $connection;

    public function __construct(
        HttpClientInterface $client,
        string $apiUrl,
        string $source,
        string $apiUser,
        string $apiPass,
        Connection $connection
    ) {
        $this->client = $client;
        $this->apiUrl = $apiUrl;
        $this->source = $source;
        $this->apiUser = $apiUser;
        $this->apiPass = $apiPass;
        $this->connection = $connection;
    }

    private function handleApiRequest(string $method, array $queryParams = []): array
    {
        try {
            $response = $this->client->request($method, $this->apiUrl, ['query' => $queryParams]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('API responded with status code: ' . $response->getStatusCode());
            }

            $content = trim($response->getContent());
            return explode("\n", $content);
        } catch (\Exception $e) {
            throw new \Exception('API request failed: ' . $e->getMessage());
        }
    }
    



    public function getListsFromDatabase(): array
    {
        $sql = "
            SELECT 
                list_id, 
                list_name, 
                campaign_id, 
                active, 
                list_description 
            FROM 
                vicidial_lists
            WHERE 
                active = 'Y' 
            ORDER BY 
                list_id ASC
        ";

        return $this->connection->fetchAllAssociative($sql);
    }

/**   public function getLists(?string $campaignId = null): array
    {
        $queryParams = [
            'source' => $this->source,
            'user' => $this->apiUser,
            'pass' => $this->apiPass,
            'function' => 'list_info',
        ];
    
        if ($campaignId) {
            $queryParams['campaign_id'] = $campaignId;
        }
    
        error_log('Params sent to API: ' . json_encode($queryParams)); // Debug log
    
        $response = $this->handleApiRequest('GET', $queryParams);
    
        return array_map(function ($line) {
            $fields = explode('|', $line);
            return [
                'list_id' => $fields[0] ?? null,
                'list_name' => $fields[1] ?? null,
                'campaign_id' => $fields[2] ?? null,
                'active' => $fields[3] ?? null,
                'list_description' => $fields[4] ?? null,
            ];
        }, $response);
    }
    **/
    
    
    

    public function getListInfo(string $listId): array
    {
        $response = $this->handleApiRequest('GET', [
            'source' => $this->source,
            'user' => $this->apiUser,
            'pass' => $this->apiPass,
            'function' => 'list_info',
            'list_id' => $listId,
        ]);

        if (empty($response)) {
            throw new \Exception('No data received for list info');
        }

        $fields = explode('|', $response[0]);

        return [
            'list_id' => $fields[0] ?? null,
            'list_name' => $fields[1] ?? null,
            'campaign_id' => $fields[2] ?? null,
            'active' => $fields[3] ?? null,
            'total_leads' => $fields[4] ?? null,
            'called_leads' => $fields[5] ?? null,
            'uncalled_leads' => $fields[6] ?? null,
            'last_update' => $fields[7] ?? null,
            'description' => $fields[8] ?? null,
        ];
    }

    public function addList(array $listData): array
    {
        $this->validateListData($listData, ['list_id', 'list_name', 'campaign_id']);

        $response = $this->handleApiRequest('POST', array_merge($listData, [
            'source' => $this->source,
            'user' => $this->apiUser,
            'pass' => $this->apiPass,
            'function' => 'add_list',
        ]));

        return [
            'success' => true,
            'message' => 'List added successfully',
            'response' => $response,
        ];
    }

    public function updateList(string $listId, array $listData): array
    {
        $this->validateListData($listData, []);

        $response = $this->handleApiRequest('POST', array_merge($listData, [
            'source' => $this->source,
            'user' => $this->apiUser,
            'pass' => $this->apiPass,
            'function' => 'update_list',
            'list_id' => $listId,
        ]));

        return [
            'success' => true,
            'message' => 'List updated successfully',
            'response' => $response,
        ];
    }

    public function getCustomFields(string $listId = null): array
    {
        $response = $this->handleApiRequest('GET', [
            'source' => $this->source,
            'user' => $this->apiUser,
            'pass' => $this->apiPass,
            'function' => 'list_custom_fields',
            'list_id' => $listId,
        ]);

        return array_map(function ($line) {
            $fields = explode('|', $line);
            return [
                'field_id' => $fields[0] ?? null,
                'field_label' => $fields[1] ?? null,
                'field_type' => $fields[2] ?? null,
                'active' => $fields[3] ?? null,
                'field_description' => $fields[4] ?? null,
            ];
        }, $response);
    }

    private function validateListData(array $listData, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (empty($listData[$field])) {
                throw new \InvalidArgumentException(sprintf('The field "%s" is required', $field));
            }
        }
    }
}
