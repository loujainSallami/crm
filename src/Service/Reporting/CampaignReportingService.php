<?php

namespace App\Service\Reporting;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class CampaignReportingService
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function getRealTimeCampaignSummary(): array
    {
        $url = 'http://localhost/vicidial/AST_timeonVDADallSUMMARY.php';

        try {
            $response = $this->client->request('GET', $url, [
                'auth_basic' => ['6666', 'custom1234'],
            ]);

            $htmlContent = $response->getContent();
            $parsedData = $this->parseHtmlTable($htmlContent);
            return $this->processData($parsedData);
        } catch (\Exception $e) {
            throw new \RuntimeException('Error fetching campaign data: ' . $e->getMessage());
        }
    }

    private function parseHtmlTable(string $htmlContent): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($htmlContent);

        $tableData = [];
        $rows = $dom->getElementsByTagName('tr');

        foreach ($rows as $row) {
            $rowData = [];
            $cells = $row->getElementsByTagName('td');
            foreach ($cells as $cell) {
                $rowData[] = trim($cell->textContent);
            }
            $tableData[] = $rowData;
        }

        return $tableData;
    }

    private function processData(array $rawData): array
    {
        $structuredData = [];
        foreach ($rawData as $item) {
            $cleanedItem = [];
            foreach ($item as $key => $value) {
                $cleanedKey = trim(str_replace("\u00a0", '', $key));
                $cleanedValue = trim(str_replace("\u00a0", '', $value));
                if (!empty($cleanedKey) && !empty($cleanedValue)) {
                    $cleanedItem[$cleanedKey] = $cleanedValue;
                }
            }

            if (!empty($cleanedItem)) {
                $structuredData[] = $cleanedItem;
            }
        }

        return [
            'headers' => array_keys($structuredData[0] ?? []),
            'rows' => $structuredData,
        ];
    }
}
