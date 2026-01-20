<?php

namespace App\Controller;

use App\Entity\CrmUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Repository\VicidialUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
final class VicidialUserController extends AbstractController
{
    private VicidialUserRepository $userRepository;
    private EntityManagerInterface $manager;
    private JWTTokenManagerInterface $jwtManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $manager,
        VicidialUserRepository $userRepository,
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->manager = $manager;
        $this->userRepository = $userRepository;
        $this->jwtManager = $jwtManager;
        $this->passwordHasher = $passwordHasher;
    }

    // ================= CREATION UTILISATEUR =================
    #[Route('/userCreate', name: 'user_create', methods: ['POST'])]
    public function userCreate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['user'], $data['pass'], $data['full_name'])) {
            return new JsonResponse([
                'status' => false,
                'message' => 'Veuillez fournir un nom d’utilisateur, un mot de passe et un nom complet.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $username = $data['user'];
        $password = $data['pass'];
        $fullName = $data['full_name'];

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->userRepository->findOneBy(['user' => $username]);
        if ($existingUser) {
            return new JsonResponse([
                'status' => false,
                'message' => 'Cet utilisateur existe déjà.'
            ], Response::HTTP_CONFLICT);
        }

        // Créer un nouvel utilisateur
        $newUser = new CrmUser();
        $newUser->setUser($username)
                ->setFullName($fullName);

        // Hachage sécurisé du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($newUser, $password);
        $newUser->setPass($hashedPassword);

        // Persister dans la base
        $this->manager->persist($newUser);
        $this->manager->flush();

        // Générer un token JWT
        $token = $this->jwtManager->create($newUser);

        return new JsonResponse([
            'status' => true,
            'message' => 'Utilisateur créé avec succès.',
            'token' => $token
        ], Response::HTTP_CREATED);
    }

    // ================= LISTE DES UTILISATEURS =================
    #[Route('/getAllUsers', name: 'get_all_users', methods: ['GET'])]
    public function getAllUsers(): JsonResponse
    {
        $users = $this->userRepository->findAll();

        if (empty($users)) {
            return new JsonResponse([
                'status' => false,
                'message' => 'Aucun utilisateur trouvé.'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = array_map(fn(CrmUser $u) => [
            'id' => $u->getUserId(),
            'user' => $u->getUser(),
            'full_name' => $u->getFullName(),
        ], $users);

        return new JsonResponse($data, Response::HTTP_OK);
    }

    // ================= UTILISATEUR ACTUEL =================
    #[Route('/me', name: 'current_user', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['status' => false, 'message' => 'Utilisateur non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ]);
    }
}
