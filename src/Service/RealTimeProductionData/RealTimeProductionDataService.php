<?php

namespace App\Service\RealTimeProductionData;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RealTimeProductionDataService
{
    private HttpClientInterface $httpClient;
    private const BASE_URL = 'http://sdch.ophony.com/vicidial/';
    private const AUTH_CREDENTIALS = ['ophony', '19admsdch20']; // Identifiants de connexion

    public function __construct(HttpClientInterface $httpClient)
    
    {
        $this->httpClient = $httpClient;
    }



    public function fetchGroupsAndUsers(): array
{
    $urlCampaigns = self::BASE_URL . 'fetch_campaigns.php'; // URL pour récupérer les campagnes
    $urlUserGroups = self::BASE_URL . 'fetch_user_groups.php'; // URL pour récupérer les groupes utilisateurs

    try {
        // Récupérer les campagnes
        $responseCampaigns = $this->httpClient->request('GET', $urlCampaigns, [
            'auth_basic' => self::AUTH_CREDENTIALS,
        ]);
        $campaigns = json_decode($responseCampaigns->getContent(), true);

        // Récupérer les groupes utilisateurs
        $responseUserGroups = $this->httpClient->request('GET', $urlUserGroups, [
            'auth_basic' => self::AUTH_CREDENTIALS,
        ]);
        $userGroups = json_decode($responseUserGroups->getContent(), true);

        return [
            'campaigns' => $campaigns ?? [],
            'user_groups' => $userGroups ?? [],
        ];
    } catch (\Exception $e) {
        throw new \Exception("Error fetching groups and users: {$e->getMessage()}");
    }
}

    

    public function downloadAgentPerformanceFile(array $params, string $localFilePath): void
    {
        $url = self::BASE_URL . 'AST_agent_performance_detail.php';
        $queryParams = http_build_query($params);
    
        try {
            // Requête pour télécharger le fichier
            $response = $this->httpClient->request('GET', "$url?$queryParams", [
                'auth_basic' => self::AUTH_CREDENTIALS,
                'headers' => ['Accept' => 'text/csv,application/octet-stream'], // Accepter les deux types de contenu
            ]);
    
            if ($response->getStatusCode() !== 200) {
                throw new \Exception("Failed to fetch the file. HTTP Status: {$response->getStatusCode()}");
            }
    
            $contentType = $response->getHeaders()['content-type'][0] ?? '';
            if (
                stripos($contentType, 'text/csv') === false &&
                stripos($contentType, 'application/octet-stream') === false
            ) {
                throw new \Exception("Invalid response format. Expected CSV or application/octet-stream, got: {$contentType}");
            }
    
            // Sauvegarder le contenu dans un fichier local
            $content = $response->getContent();
            file_put_contents($localFilePath, $content);
        } catch (\Exception $e) {
            throw new \Exception("Error downloading file: {$e->getMessage()}");
        }
    }
    
    public function generateRealtimeReportCSV(array $params, string $localFilePath): void
{
    $url = self::BASE_URL . 'realtime_report.php'; // Endpoint du rapport en temps réel
    $queryParams = http_build_query($params);

    try {
        $response = $this->httpClient->request('GET', "$url?$queryParams", [
            'auth_basic' => self::AUTH_CREDENTIALS,
            'headers' => ['Accept' => 'text/csv'], // Accepter le format CSV
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Failed to fetch the report. HTTP Status: {$response->getStatusCode()}");
        }

        // Sauvegarder le fichier CSV
        $content = $response->getContent();
        file_put_contents($localFilePath, $content);
    } catch (\Exception $e) {
        throw new \Exception("Error generating report: {$e->getMessage()}");
    }
}


}


