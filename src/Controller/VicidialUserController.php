<?php

namespace App\Controller;

use App\Entity\CRM\CrmUser;
use App\Repository\CrmUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

final class VicidialUserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CrmUserRepository $userRepository,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    // ================= CREATION UTILISATEUR =================
    #[Route('/userCreate', name: 'user_create', methods: ['POST'])]
    public function userCreate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // ✅ on standardise: username + pass
        if (!is_array($data) || empty($data['username']) || empty($data['pass'])) {
            return $this->json([
                'status' => false,
                'message' => 'Champs requis: username, pass.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $username = trim((string) $data['username']);
        $plainPassword = (string) $data['pass'];

        // ✅ vérifier existence
        $existingUser = $this->userRepository->findOneBy(['username' => $username]);
        if ($existingUser) {
            return $this->json([
                'status' => false,
                'message' => 'Cet utilisateur existe déjà.'
            ], Response::HTTP_CONFLICT);
        }

        // ✅ création user
        $newUser = new CrmUser();
        $newUser->setUsername($username);

        // hash
        $hashed = $this->passwordHasher->hashPassword($newUser, $plainPassword);
        $newUser->setPassword($hashed);

        // champs optionnels
        if (isset($data['full_name'])) {
            $newUser->setFullName($data['full_name']);
        }
        if (isset($data['user_level'])) {
            $newUser->setUserLevel((int) $data['user_level']);
        }

        $this->entityManager->persist($newUser);
        $this->entityManager->flush();

        // ✅ JWT
        $token = $this->jwtManager->create($newUser);

        return $this->json([
            'status' => true,
            'message' => 'Utilisateur créé avec succès.',
            'token' => $token,
            'user' => [
                'id' => $newUser->getId(),
                'username' => $newUser->getUsername(),
                'fullName' => $newUser->getFullName(),
                'userLevel' => $newUser->getUserLevel(),
            ]
        ], Response::HTTP_CREATED);
    }

    // ================= RECUPERER TOUS LES UTILISATEURS =================
    #[Route('/api/getAllUsers', name: 'get_all_users', methods: ['GET'])]
    public function getAllUsers(): JsonResponse
    {
        // ✅ éviter hydration + relations
        $users = $this->userRepository->createQueryBuilder('u')
            ->select('u.user_id, u.username, u.full_name, u.user_level')
            ->getQuery()
            ->getArrayResult();

        return $this->json([
            'status' => true,
            'users' => $users
        ], Response::HTTP_OK);
    }
}