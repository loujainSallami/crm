<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

class VicidialApiService
{
    private HttpClientInterface $client;
    private string $apiUrl;
    private string $apiUser;
    private string $apiPass;
    private string $source;

    public function __construct(HttpClientInterface $client, string $apiUrl, string $apiUser, string $apiPass, string $source)
    {
        $this->client = $client;
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiUser = $apiUser;
        $this->apiPass = $apiPass;
        $this->source = $source;
    }

    // --- VERSION API ---
    public function getVersion(): array
    {
        if (empty($this->apiUser) || empty($this->apiPass)) {
            return ['error' => 'User and password are required', 'code' => 401];
        }

        try {
            $url = "{$this->apiUrl}?source={$this->source}&user={$this->apiUser}&pass={$this->apiPass}&function=version";
            $response = $this->client->request('GET', $url);
            return ['version' => $response->getContent(false)];
        } catch (TransportExceptionInterface | ClientExceptionInterface $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // --- GET CAMPAIGNS ---
    public function getCampaigns(): JsonResponse
    {
        try {
            $response = $this->client->request('GET', $this->apiUrl, [
                'query' => [
                    'source' => $this->source,
                    'user' => $this->apiUser,
                    'pass' => $this->apiPass,
                    'function' => 'campaigns_list',
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return new JsonResponse(['error' => 'Failed to retrieve campaigns'], 500);
            }

            $data = explode("\n", trim($response->getContent(false)));
            $campaigns = [];

            foreach ($data as $line) {
                if (empty($line)) continue;
                $fields = explode('|', $line);
                if (count($fields) < 10) continue;

                $campaigns[] = [
                    'ID' => $fields[0],
                    'Name' => $fields[1],
                    'Active' => $fields[2],
                    'Dial Method' => $fields[3],
                    'Dial Ratio' => $fields[4],
                    'Leads Not Called' => $fields[5],
                    'Leads Called' => $fields[6],
                    'Leads To Call' => $fields[7],
                    'Total Leads' => $fields[8],
                    'List Count' => $fields[9],
                ];
            }

            return new JsonResponse($campaigns);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // --- CREATE CAMPAIGN ---
    public function createCampaign(array $data): array
    {
        $query = [
            'source' => $this->source,
            'user' => $this->apiUser,
            'pass' => $this->apiPass,
            'function' => 'add_campaign',
            'campaign_name' => urlencode($data['campaign_name'] ?? ''),
            'campaign_description' => urlencode($data['campaign_description'] ?? ''),
            'active' => $data['active'] ?? 'N',
        ];

        $url = "{$this->apiUrl}?".http_build_query($query);

        try {
            $response = $this->client->request('GET', $url);
            return ['status' => $response->getStatusCode(), 'content' => $response->getContent(false)];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // --- GET USERS ---
    public function getUsers(): JsonResponse
    {
        try {
            $response = $this->client->request('GET', $this->apiUrl, [
                'query' => [
                    'source' => $this->source,
                    'user' => $this->apiUser,
                    'pass' => $this->apiPass,
                    'function' => 'users_list',
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return new JsonResponse(['error' => 'Failed to retrieve users'], 500);
            }

            $lines = explode("\n", trim($response->getContent(false)));
            $users = [];

            foreach ($lines as $line) {
                if (empty($line)) continue;
                $fields = explode('|', $line);
                if (count($fields) < 4) continue;

                $users[] = [
                    'ID' => $fields[0],
                    'Username' => $fields[1],
                    'Status' => $fields[2],
                    'Full Name' => $fields[3],
                ];
            }

            return new JsonResponse($users);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
