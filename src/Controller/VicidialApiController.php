<?php
namespace App\Controller;

use App\Service\VicidialApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class VicidialApiController extends AbstractController
{
    private $vicidialApiService;

    public function __construct(VicidialApiService $vicidialApiService)
    {
        $this->vicidialApiService = $vicidialApiService;
    }

    /**
     * @Route("/api/vicidial/getVersion", name="get_version", methods={"GET"})
     */
    public function getVersion(): JsonResponse
    {
        $user = $this->getUser(); // Utilisateur connecté
        $version = $this->vicidialApiService->getVersion();

        return new JsonResponse([
            'version' => $version,
            'user' => $user ? $user->getUserIdentifier() : null
        ]);
    }

    /**
     * @Route("/api/vicidial/getCampaigns", name="get_campaigns", methods={"GET"})
     */
    public function getCampaigns(): JsonResponse
    {
        return $this->vicidialApiService->getCampaigns();
    }

    /**
     * @Route("/api/vicidial/addCampaign", name="add_campaign", methods={"POST"})
     */
    public function addCampaign(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['campaign_name']) || empty($data['campaign_description'])) {
            return new JsonResponse(['success' => false, 'message' => 'Campaign name and description are required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $response = $this->vicidialApiService->createCampaign($data);

        if (isset($response['error'])) {
            return new JsonResponse(['success' => false, 'message' => $response['error']], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['success' => true, 'message' => 'Campaign created successfully'], JsonResponse::HTTP_CREATED);
    }

    /**
     * @Route("/api/vicidial/getUsers", name="get_users", methods={"GET"})
     */
    public function getUsers(): JsonResponse
    {
        $users = $this->vicidialApiService->getUsers();

        if (empty($users)) {
            return new JsonResponse(['message' => 'No users found'], 404);
        }

        return new JsonResponse($users);
    }

    /**
     * @Route("/api/vicidial/updateCampaign/{campaignId}", name="update_campaign", methods={"PUT"})
     */
    public function updateCampaign(int $campaignId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $result = $this->vicidialApiService->updateCampaign($campaignId, $data);
            return new JsonResponse($result, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to update campaign', 'details' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/api/vicidial/addUser", name="add_user", methods={"POST"})
     */
    public function addUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $result = $this->vicidialApiService->addUser($data);
            return new JsonResponse($result, JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to add user', 'details' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/api/vicidial/updateUser", name="update_user", methods={"PUT"})
     */
    public function updateUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $result = $this->vicidialApiService->updateUser($data);
            return new JsonResponse($result, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to update user', 'details' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/api/vicidial/deleteUser/{userId}", name="delete_user", methods={"DELETE"})
     */
    public function deleteUser(string $userId): JsonResponse
    {
        try {
            $result = $this->vicidialApiService->deleteUser($userId);
            return new JsonResponse($result, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to delete user', 'details' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/api/vicidial/getUserDetails/{userId}", name="get_user_details", methods={"GET"})
     */
    public function getUserDetails(string $userId): JsonResponse
    {
        try {
            $result = $this->vicidialApiService->getUserDetails($userId);
            return new JsonResponse($result, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to get user details', 'details' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/api/vicidial/createList", name="create_list", methods={"POST"})
     */
    public function createList(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $result = $this->vicidialApiService->createList($data);

            if (isset($result['error'])) {
                return new JsonResponse($result, JsonResponse::HTTP_BAD_REQUEST);
            }

            return new JsonResponse($result, JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to create list', 'details' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/api/vicidial/updateList/{listId}", name="update_list", methods={"PUT"})
     */
    public function updateList(int $listId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $result = $this->vicidialApiService->updateList($listId, $data);
            return new JsonResponse($result, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to update list', 'details' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/api/vicidial/getListInfo/{listId}", name="get_list_info", methods={"GET"})
     */
    public function getListInfo(int $listId): JsonResponse
    {
        try {
            $result = $this->vicidialApiService->getListInfo($listId);
            return new JsonResponse($result, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to get list info', 'details' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}
