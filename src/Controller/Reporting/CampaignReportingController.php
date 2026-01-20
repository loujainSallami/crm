<?php

namespace App\Controller\Reporting;

use App\Service\Reporting\CampaignReportingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CampaignReportingController extends AbstractController
{
    private CampaignReportingService $campaignReportingService;

    public function __construct(CampaignReportingService $campaignReportingService)
    {
        $this->campaignReportingService = $campaignReportingService;
    }

    /**
     * @Route("/api/vicidial/report", name="vicidial_report", methods={"GET"})
     */
    public function getRealTimeCampaignSummary(): JsonResponse
    {
        try {
            $data = $this->campaignReportingService->getRealTimeCampaignSummary();
            return $this->json($data, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Failed to fetch campaign data.', 'details' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
