<?php

namespace App\Service\RealTimeReport;

use Doctrine\DBAL\Connection;

class RealTimeReportService
{

    private HttpClientInterface $httpClient;
    private const BASE_URL = 'http://sdch.ophony.com/vicidial/';
    private const AUTH_CREDENTIALS = ['ophony', '19admsdch20']; // Identifiants de connexion

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Récupère les données du rapport en temps réel.
     */
    public function downloadRealTimeReportFile(array $params, string $localFilePath): void
    {
        $url = self::BASE_URL . 'AST_timeonVDADallSUMMARY.php?group=&RR=40&DB=0&adastats=&types=SHOW%20ALL%20CAMPAIGNS&file_download=1';
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
}
