<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class VicidialApiService
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $apiUrl,
        private readonly string $apiUser,
        private readonly string $apiPass,
        private readonly string $source,
    ) {}

    private function baseQuery(string $function, array $extra = []): array
    {
        return array_merge([
            'source' => $this->source,
            'user' => $this->apiUser,
            'pass' => $this->apiPass,
            'function' => $function,
        ], $extra);
    }

    /**
     * Lance une requête GET vers Vicidial avec timeout + retourne le contenu brut
     * @throws \RuntimeException en cas timeout / réseau / 4xx/5xx
     */
    private function requestVicidial(array $query): string
    {
        if (empty($this->apiUser) || empty($this->apiPass)) {
            throw new \RuntimeException('VICIDIAL credentials missing (user/pass).');
        }

        try {
            $response = $this->client->request('GET', rtrim($this->apiUrl, '/'), [
                'query' => $query,
                'timeout' => 8, // ✅ important : évite 60s
                'headers' => [
                    'User-Agent' => 'CRM-Vicidial/1.0',
                ],
            ]);

            // getContent(false) => pas d’exception auto, on gère nous-mêmes
            $status = $response->getStatusCode();
            $content = $response->getContent(false);

            if ($status < 200 || $status >= 300) {
                throw new \RuntimeException("Vicidial HTTP $status: ".substr($content, 0, 300));
            }

            return $content;
        } catch (TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface $e) {
            // Ici tu vas tomber quand le serveur est injoignable (timeout)
            throw new \RuntimeException('Vicidial unreachable: '.$e->getMessage(), 0, $e);
        }
    }

    // --- VERSION API ---
    public function getVersion(): array
    {
        $content = $this->requestVicidial($this->baseQuery('version'));
        return ['version' => trim($content)];
    }

    // --- GET CAMPAIGNS ---
    public function getCampaigns(): array
    {
        $content = $this->requestVicidial($this->baseQuery('campaigns_list'));

        $lines = explode("\n", trim($content));
        $campaigns = [];

        foreach ($lines as $line) {
            if ($line === '') continue;
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

        return $campaigns;
    }

    // --- CREATE CAMPAIGN ---
    public function createCampaign(array $data): array
    {
        $query = $this->baseQuery('add_campaign', [
            // ⚠️ ne pas urlencode ici, http client le fait via query
            'campaign_name' => $data['campaign_name'] ?? '',
            'campaign_description' => $data['campaign_description'] ?? '',
            'active' => $data['active'] ?? 'N',
        ]);

        $content = $this->requestVicidial($query);

        return [
            'raw' => $content,
        ];
    }

    // --- GET USERS ---
    public function getUsers(): array
    {
        $content = $this->requestVicidial($this->baseQuery('users_list'));

        $lines = explode("\n", trim($content));
        $users = [];

        foreach ($lines as $line) {
            if ($line === '') continue;
            $fields = explode('|', $line);
            if (count($fields) < 4) continue;

            $users[] = [
                'ID' => $fields[0],
                'Username' => $fields[1],
                'Status' => $fields[2],
                'Full Name' => $fields[3],
            ];
        }

        return $users;
    }
}