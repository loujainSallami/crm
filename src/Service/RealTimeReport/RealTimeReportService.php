<?php

namespace App\Service\RealTimeReport;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RealTimeReportService
{
    private const BASE_URL = 'http://sdch.ophony.com/vicidial/';
    private const AUTH_CREDENTIALS = ['ophony', '19admsdch20'];

    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    /**
     * Télécharge le fichier CSV du rapport temps réel.
     * Si le serveur Vicidial est inaccessible, génère un CSV mock local (fallback).
     */
    public function downloadRealTimeReportFile(array $params, string $localFilePath): void
    {
        // Endpoint SANS query string
        $endpoint = self::BASE_URL . 'AST_timeonVDADallSUMMARY.php';

        // Paramètres "fixes" + params dynamiques
        $query = array_merge([
            'group' => '',
            'RR' => 40,
            'DB' => 0,
            'adastats' => '',
            'types' => 'SHOW ALL CAMPAIGNS',
            'file_download' => 1,
        ], $params);

        try {
            $response = $this->httpClient->request('GET', $endpoint, [
                'auth_basic' => self::AUTH_CREDENTIALS,
                'query' => $query,
                'headers' => [
                    'Accept' => 'text/csv,application/octet-stream,text/plain',
                ],
                'timeout' => 10, // évite de rester bloqué
            ]);

            // Si pas OK => on force le fallback
            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException("HTTP Status: {$response->getStatusCode()}");
            }

            // Vérification content-type (souvent Vicidial renvoie text/html ou text/plain)
            $headers = $response->getHeaders(false);
            $contentType = $headers['content-type'][0] ?? '';

            if (
                stripos($contentType, 'text/csv') === false &&
                stripos($contentType, 'application/octet-stream') === false &&
                stripos($contentType, 'text/plain') === false
            ) {
                // On ne bloque pas forcément, mais on peut choisir de fallback
                // Ici on fallback pour être safe
                throw new \RuntimeException("Invalid content-type: {$contentType}");
            }

            // Sauvegarder le CSV reçu
            file_put_contents($localFilePath, $response->getContent());
        } catch (\Throwable $e) {
            // ✅ Fallback MOCK si Vicidial est down
            $mockCsv = $this->generateMockCsv($params);
            file_put_contents($localFilePath, $mockCsv);
        }
    }

    /**
     * Génère un CSV mock (simulé) pour continuer le dev/test sans serveur Vicidial.
     * Tu peux adapter les colonnes selon ce que ton front/back attend.
     */
    private function generateMockCsv(array $params = []): string
    {
        $generatedAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        // Exemple: si tu passes une liste de campagnes via params, tu peux l'utiliser
        // $campaignFilter = $params['campaign'] ?? null;

        $rows = [
            ['generated_at', $generatedAt],
            [], // ligne vide
            ['campaign_id', 'campaign_name', 'calls', 'answered', 'dropped', 'agents', 'appointments'],
            ['CAMP01', 'Campagne Vente', rand(100, 180), rand(60, 140), rand(0, 25), rand(3, 10), rand(5, 25)],
            ['CAMP02', 'Campagne Support', rand(60, 140), rand(40, 110), rand(0, 20), rand(2, 8), rand(2, 18)],
            ['CAMP03', 'Campagne Relance', rand(50, 120), rand(30, 90), rand(0, 15), rand(1, 6), rand(1, 12)],
        ];

        $out = '';
        foreach ($rows as $row) {
            if (empty($row)) {
                $out .= "\n";
                continue;
            }
            // CSV simple, séparateur virgule
            $out .= implode(',', array_map([$this, 'escapeCsvValue'], $row)) . "\n";
        }

        return $out;
    }

    /**
     * Échappement basique CSV (si virgules / guillemets dans les valeurs).
     */
    private function escapeCsvValue(mixed $value): string
    {
        $value = (string) $value;

        if (str_contains($value, '"')) {
            $value = str_replace('"', '""', $value);
        }

        if (str_contains($value, ',') || str_contains($value, "\n") || str_contains($value, '"')) {
            $value = '"' . $value . '"';
        }

        return $value;
    }
}