<?php

namespace App\Controller\RealTimeProduction;

use App\Service\RealTimeProductionData\RealTimeProductionDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class RealTimeProductionController extends AbstractController
{
    #[Route('/api/vicidial/realtime-report', name: 'realtime_report', methods: ['GET'])]
    public function realtimeReport(Request $request, RealTimeProductionDataService $service): StreamedResponse
    {
        $params = $request->query->all();

        // 1) Vicidial -> fichier temporaire dans var/
        $localFilePath = $this->getParameter('kernel.project_dir') . '/var/reports/realtime_report.csv';

        // 2) Fichier ETL Python (fallback)
        $etlFilePath = $this->getParameter('kernel.project_dir') . '/var/etl/realtime_report.csv';

        try {
            // Tentative Vicidial
            $service->generateRealtimeReportCSV($params, $localFilePath);
            $fileToSend = $localFilePath;
        } catch (\Throwable $e) {
            // Fallback ETL si disponible
            if (is_file($etlFilePath)) {
                $fileToSend = $etlFilePath;
            } else {
                // Fallback mock minimal
                $mock = "campaign_id,calls,answered,agents\nCAMP01,120,80,6\nCAMP02,90,60,4\n";
                return new StreamedResponse(function () use ($mock) {
                    echo $mock;
                }, 200, [
                    'Content-Type' => 'text/csv; charset=utf-8',
                    'Content-Disposition' => 'attachment; filename="realtime_report_mock.csv"',
                ]);
            }
        }

        // Envoi du CSV (download)
        return new StreamedResponse(function () use ($fileToSend) {
            readfile($fileToSend);
        }, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="realtime_report.csv"',
        ]);
    }
}