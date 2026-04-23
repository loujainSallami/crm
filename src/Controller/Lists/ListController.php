<?php

namespace App\Controller\Lists;

use App\Service\Lists\ListService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/vicidial/lists')]
class ListController extends AbstractController
{
    public function __construct(private ListService $listService)
    {
    }

    #[Route('', name: 'api_get_lists', methods: ['GET'])]
    public function getLists(): JsonResponse
    {
        try {
            $lists = $this->listService->getListsFromDatabase();

            return $this->json([
                'success' => true,
                'data' => $lists,
            ], 200);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des listes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/create', name: 'api_add_list', methods: ['POST'])]
    public function addList(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!is_array($data)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Payload JSON invalide',
                ], 400);
            }

            $result = $this->listService->addList($data);

            return $this->json([
                'success' => true,
                'message' => 'Liste créée avec succès',
                'data' => $result,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la liste',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/{listId}', name: 'api_get_list_by_id', methods: ['GET'])]
    public function getListById(string $listId): JsonResponse
    {
        try {
            $list = $this->listService->getListInfo($listId);

            return $this->json([
                'success' => true,
                'data' => $list,
            ], 200);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la liste',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    #[Route('/test-post-route', name: 'api_test_post_route', methods: ['GET'])]
    public function testPostRoute(): JsonResponse
    {
        return $this->json(['ok' => true]);
    }
    #[Route('/update/{listId}', name: 'api_update_list', methods: ['POST', 'PUT'])]
    public function updateList(string $listId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!is_array($data)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Payload JSON invalide',
                ], 400);
            }

            $result = $this->listService->updateList($listId, $data);

            return $this->json([
                'success' => true,
                'message' => 'Liste mise à jour avec succès',
                'data' => $result,
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la liste',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}