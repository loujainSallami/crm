<?php

namespace App\Controller\UsersController\Group;

use App\Service\Users\Group\GroupService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GroupController extends AbstractController
{
    private GroupService $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    /**
     * @Route("/api/vicidial/group", name="api_add_group", methods={"POST"})
     */
    public function addGroup(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['user_group'], $data['group_name'])) {
            return new JsonResponse(['error' => 'Les champs user_group et group_name sont requis.'], 400);
        }

        return $this->groupService->addGroup($data);
    }

     /**
 * @Route("/api/vicidial/group/{userGroup}", name="api_update_group", methods={"PUT"})
 */
public function updateGroup(string $userGroup, Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (!isset($data['user_group'], $data['group_name'])) {
        return new JsonResponse(['error' => 'Les champs user_group et group_name sont requis.'], 400);
    }

    return $this->groupService->updateGroup($userGroup, $data);
}


/**
 * @Route("/api/vicidial/group/{userGroup}", name="api_delete_group", methods={"DELETE"})
 */
public function deleteGroup(string $userGroup): JsonResponse
{
    return $this->groupService->deleteGroup($userGroup);
}

    /**
     * @Route("/api/vicidial/groups", name="api_get_groups", methods={"GET"})
     */
    public function getGroups(): JsonResponse
    {
        return $this->groupService->getGroups();
    }

        /**
     * @Route("/api/vicidial/user-groups", name="api_get_user_groups", methods={"GET"})
     */
    public function getUserGroups(): JsonResponse
    {
        try {
            $userGroups = $this->groupService->getUserGroupsOnly();
            return new JsonResponse($userGroups, 200);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la récupération des user_groups.', 'message' => $e->getMessage()], 500);
        }
    }
}
