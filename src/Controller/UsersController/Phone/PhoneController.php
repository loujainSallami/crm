<?php

namespace App\Controller\UsersController\Phone;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Users\Phone\PhoneService;

class PhoneController extends AbstractController
{
    private PhoneService $phoneService;

    public function __construct(PhoneService $phoneService)
    {
        $this->phoneService = $phoneService;
    }

    /**
     * @Route("/api/vicidial/phones", name="api_get_phones", methods={"GET"})
     */
    public function getPhones(): JsonResponse
    {
        return $this->phoneService->getPhones();
    }

/**
 * @Route("/api/vicidial/add_phone", name="api_add_phone", methods={"POST"})
 */
public function addPhone(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    return $this->phoneService->addPhone($data);
}

    /**
     * @Route("/api/vicidial/phone", name="api_update_phone", methods={"PUT"})
     */
    public function updatePhone(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        return $this->phoneService->updatePhone($data, 'update');
    }

    /**
     * @Route("/api/vicidial/phone", name="api_delete_phone", methods={"DELETE"})
     */
    public function deletePhone(Request $request): JsonResponse
    {
        $extension = $request->query->get('extension');
        $serverIp = $request->query->get('server_ip');

        if (!$extension || !$serverIp) {
            return new JsonResponse([
                'error' => 'Both extension and server_ip are required.',
                'received' => [
                    'extension' => $extension,
                    'server_ip' => $serverIp
                ]
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return $this->phoneService->deletePhonePhysically($extension, $serverIp);
    }
}