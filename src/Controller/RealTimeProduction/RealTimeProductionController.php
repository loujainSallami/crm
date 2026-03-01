<?php

namespace App\Controller\RealTimeProduction;

use App\Service\RealTimeProductionData\RealTimeProductionDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RealTimeProductionController extends AbstractController
{
    private const LOCAL_FILE_PATH = '/home/krch/crm1/src/Controller/RealTimeProduction/agent_performance.csv';

    private RealTimeProductionDataService $realTimeService;

    public function __construct(RealTimeProductionDataService $realTimeService)
    {
        $this->realTimeService = $realTimeService;
    }







/**
 * @Route("/api/vicidial/dynamic-download1", name="dynamic_download1", methods={"GET"})
 */
public function dynamicDownload1(Request $request, RealTimeProductionDataService $realTimeService): JsonResponse
{
    try {
        $params = $request->query->all();
        $localFilePath = '/home/krch/crm1/src/Controller/RealTimeProduction/agent_performance1.csv';
        $assetsFilePath = '/home/krch/crm1/frontend/src/assets/agent_performance1.csv'; // Chemin vers le répertoire assets

        // Générer le fichier CSV
        $realTimeService->downloadAgentPerformanceFile($params, $localFilePath);

        // Copier le fichier dans assets
        if (!copy($localFilePath, $assetsFilePath)) {
            throw new \Exception('Failed to copy the CSV file to assets.');
        }

        // Lire le contenu du fichier CSV
        $csvContent = file_get_contents($localFilePath);

        if ($csvContent === false) {
            throw new \Exception('Failed to read the CSV file.');
        }

        return new JsonResponse([
            'message' => 'Fichier téléchargé avec succès',
            'data' => $csvContent, // Inclure le contenu CSV
        ], 200);
    } catch (\Exception $e) {
        return new JsonResponse(['error' => $e->getMessage()], 500);
    }
}


/**
* @Route("/api/vicidial/dynamic-download2", name="dynamic_download2", methods={"GET"})
*/

public function dynamicDownload2(Request $request, RealTimeProductionDataService $realTimeService): JsonResponse
{
   try {
       $params = $request->query->all();
       $localFilePath = '/home/krch/crm1/src/Controller/RealTimeProduction/agent_performance2.csv';
       $assetsFilePath = '/home/krch/crm1/frontend/src/assets/agent_performance2.csv'; // Chemin vers le répertoire assets


       // Générer le fichier CSV
       $realTimeService->downloadAgentPerformanceFile($params, $localFilePath);

        // Copier le fichier dans assets
        if (!copy($localFilePath, $assetsFilePath)) {
            throw new \Exception('Failed to copy the CSV file to assets.');
        }

       // Lire le contenu du fichier CSV
       $csvContent = file_get_contents($localFilePath);

       if ($csvContent === false) {
           throw new \Exception('Failed to read the CSV file.');
       }

       return new JsonResponse([
           'message' => 'Fichier téléchargé avec succès',
           'data' => $csvContent, // Inclure le contenu CSV
       ], 200);
   } catch (\Exception $e) {
       return new JsonResponse(['error' => $e->getMessage()], 500);
   }
}

    /**
 * @Route("/api/vicidial/realtime-report", name="realtime_report", methods={"GET"})
 */
public function generateRealtimeReport(Request $request, RealTimeProductionDataService $realTimeService): JsonResponse
{
    try {
        $params = $request->query->all(); // Obtenez les paramètres de la requête

        // Chemin pour enregistrer le fichier CSV
        $localFilePath = '/home/krch/crm1/src/Controller/RealTimeProduction/realtime_report.csv';

        // Appeler un service pour générer le fichier CSV à partir du rapport
        $realTimeService->generateRealtimeReportCSV($params, $localFilePath);

        // Lire le contenu du fichier pour le retourner
        $csvContent = file_get_contents($localFilePath);

        if ($csvContent === false) {
            throw new \Exception('Failed to read the CSV file.');
        }

        return new JsonResponse([
            'message' => 'Realtime report generated successfully.',
            'data' => $csvContent, // Retournez le contenu CSV
        ], 200);
    } catch (\Exception $e) {
        return new JsonResponse(['error' => $e->getMessage()], 500);
    }
}




    /**
     * Valider et normaliser les paramètres pour les différentes routes
     */
    private function validateParams(Request $request, array $requiredKeys): array
    {
        $params = [];
        foreach ($requiredKeys as $key) {
            $value = $request->query->get($key);

            if ($value === null || $value === '') {
                throw new \InvalidArgumentException(sprintf('Missing required parameter: %s', $key));
            }

            // Normaliser les paramètres avec des tableaux pour les clés multiples
            if (str_ends_with($key, '[]')) {
                $params[$key] = is_array($value) ? $value : [$value];
            } else {
                $params[$key] = $value;
            }
        }
        return $params;
    }
}
