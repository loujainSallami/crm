<?php

namespace App\Controller\Lists;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Lists\ListService;

class ListController extends AbstractController
{
    private ListService $listService;

    public function __construct(ListService $listService)
    {
        $this->listService = $listService;
    }

    /**
     * Récupère les informations détaillées d'une liste.
     *
     * @Route("/api/vicidial/list/{listId}", name="api_get_list_info", methods={"GET"})
     */
    public function getListInfo(string $listId): JsonResponse
    {
        if (empty($listId)) {
            return new JsonResponse(['error' => 'listId is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $data = $this->listService->getListInfo($listId);
            return new JsonResponse($data, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to retrieve list info', 'details' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Ajoute une nouvelle liste.
     *
     * @Route("/api/vicidial/list", name="api_add_list", methods={"POST"})
     */
    public function addList(Request $request): JsonResponse
    {
        $listData = json_decode($request->getContent(), true);

        if (!isset($listData['list_id'], $listData['list_name'], $listData['campaign_id'])) {
            return new JsonResponse(['error' => 'Missing required fields: list_id, list_name, campaign_id'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $data = $this->listService->addList($listData);
            return new JsonResponse($data, JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to add list', 'details' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }


/**
 * @Route("/api/vicidial/lists", name="api_get_lists", methods={"GET"})
 */
/** public function getLists(Request $request): JsonResponse
{
    try {
        $campaignId = $request->query->get('campaign_id');
        $lists = $this->listService->getLists($campaignId);
        return new JsonResponse($lists, JsonResponse::HTTP_OK);
    } catch (\Exception $e) {
        return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
    }
}
    **/

/**
     * @Route("/api/vicidial/lists", name="api_get_lists", methods={"GET"})
     */
    public function getLists(): JsonResponse
    {
        try {
            $lists = $this->listService->getListsFromDatabase();
            return new JsonResponse($lists, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }


    /**
     * Récupère les champs personnalisés d'une liste ou de toutes les listes.
     *
     * @Route("/api/vicidial/list/custom-fields/{listId?}", name="api_get_list_custom_fields", methods={"GET"})
     */
    public function getCustomFields(string $listId = ''): JsonResponse
    {
        try {
            $customFields = $this->listService->getCustomFields($listId);
            return new JsonResponse($customFields, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to retrieve custom fields', 'details' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Met à jour une liste existante.
     *
     * @Route("/api/vicidial/list/{listId}", name="api_update_list", methods={"PUT"})
     */
    public function updateList(string $listId, Request $request): JsonResponse
    {
        if (empty($listId)) {
            return new JsonResponse(['error' => 'listId is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data)) {
            return new JsonResponse(['error' => 'No data provided'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->listService->updateList($listId, $data);
            return new JsonResponse($result, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to update list', 'details' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}
