<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\VicidialDbService;

class VicidialController extends AbstractController
{
    private $vicidialDbService;

    public function __construct(VicidialDbService $vicidialDbService)
    {
        $this->vicidialDbService = $vicidialDbService;
    }

    #[Route('/vicidial/campaigns/add', name: 'vicidial_add_campaign', methods: ['POST', 'OPTIONS'])]
    public function addCampaign(Request $request): Response
    {
        // Gestion du pré-vol CORS
        if ($request->getMethod() === 'OPTIONS') {
            return new Response('', 204, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type',
                'Access-Control-Max-Age' => 3600,
            ]);
        }

        $data = json_decode($request->getContent(), true);

        // Validation des données
        if (empty($data['campaign_name']) || empty($data['campaign_description']) || !isset($data['lead_recycle']) || !isset($data['web_lead_recycle'])) {
            return $this->json([
                'success' => false,
                'message' => 'Campaign name, description, lead_recycle, and web_lead_recycle are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Création de la campagne via VicidialDbService
        $response = $this->vicidialDbService->createCampaign($data);

        if (isset($response['error'])) {
            return $this->json([
                'success' => false,
                'message' => $response['error']
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($response['success']) {
            return $this->json([
                'success' => true,
                'message' => 'Campaign created successfully'
            ], Response::HTTP_CREATED);
        } else {
            return $this->json([
                'success' => false,
                'message' => 'Failed to create campaign',
                'details' => $response['details']
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
