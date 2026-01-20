<?php

namespace App\Controller;
use App\Entity\Appointment;
use App\Repository\AppointmentRepository;
use App\Repository\VicidialUserRepository;
use Psr\Log\LoggerInterface;
use App\Entity\VicidialLead;
use App\Entity\VicidialUser ;
use App\Service\AppointmentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/appointments')]
class AppointmentController extends AbstractController
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
        private readonly SerializerInterface $serializer,
        private readonly EntityManagerInterface $entityManager, // Ajout de l'EntityManager
        private readonly LoggerInterface $logger,// <-- injecté ici

    ) {
    }
   
    #[Route('', name: 'appointment_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
    
        $lead = $this->entityManager->getRepository(VicidialLead::class)->find($data['leadId']);
        $user = $this->entityManager->getRepository(VicidialUser::class)->find($data['userId']);
    
        if (!$lead || !$user) {
            return new JsonResponse(['message' => 'Lead ou User non trouvé'], Response::HTTP_BAD_REQUEST);
        }
    
        $appointment = new Appointment();
        $appointment->setDescription($data['description']);
        $appointment->setLead($lead);
        $appointment->setUser($user);
    
        try {
            $appointment->setStartTime(new \DateTime($data['startTime']));
            $appointment->setEndTime(new \DateTime($data['endTime']));
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Format de date invalide'], Response::HTTP_BAD_REQUEST);
        }
    
        $this->entityManager->persist($appointment);
        $this->entityManager->flush();
    
        return new JsonResponse(['message' => 'Rendez-vous créé'], Response::HTTP_CREATED);
    }

/*
public function create(Request $request): JsonResponse
{
    /** @var VicidialUser $currentUser */
  /*  $currentUser = $this->getUser();

    try {
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return $this->json(['error' => 'JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        // Détermination de l'utilisateur à associer au rendez-vous
        $userToAssign = $currentUser;

        // ✅ Si admin et user envoyé → on l’associe
        if (in_array('ROLE_ADMIN', $currentUser->getRoles()) && !empty($data['user'])) {
            $foundUser = $this->entityManager->getRepository(VicidialUser::class)->findOneBy(['user' => $data['user']]);
            if (!$foundUser) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_BAD_REQUEST);
            }
            $userToAssign = $foundUser;
        }

        $lead = null;
        if (!empty($data['leadId'])) {
            $lead = $this->entityManager->getRepository(VicidialLead::class)->find($data['leadId']);
            if (!$lead) {
                return $this->json(['error' => 'Lead introuvable'], Response::HTTP_BAD_REQUEST);
            }
        }

        $appointment = $this->appointmentService->createAppointment(
            $userToAssign,
            new \DateTime($data['startTime']),
            new \DateTime($data['endTime']),
            $data['description'],
            $lead,
            
        );

        return new JsonResponse(
            $this->serializer->serialize($appointment, 'json', ['groups' => 'appointment:read']),
            Response::HTTP_CREATED,
            [],
            true
        );

    } catch (\Throwable $e) {
        return $this->json([
            'error' => 'Une erreur est survenue',
            'message' => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}*/

    /**
     * Met à jour un rendez-vous
     */
    
     #[Route('/{id}', name: 'appointment_update', methods: ['PUT'])]
     public function update(Request $request, int $id): JsonResponse
     {
         try {
             $appointment = $this->appointmentService->getAppointmentDetails($id);
     
             if (!$appointment) {
                 return $this->json(['error' => 'Rendez-vous non trouvé'], Response::HTTP_NOT_FOUND);
             }
     
             $data = json_decode($request->getContent(), true);
     
             if (!isset($data['leadId'], $data['userId'], $data['startTime'], $data['endTime'], $data['description'])) {
                 return $this->json(['error' => 'Données manquantes dans la requête'], Response::HTTP_BAD_REQUEST);
             }
     
             // Vérifier lead
             $lead = $this->entityManager->getRepository(VicidialLead::class)->find($data['leadId']);
             if (!$lead) {
                 return $this->json(['error' => 'Lead introuvable'], Response::HTTP_BAD_REQUEST);
             }
     
             // Vérifier user
             $user = $this->entityManager->getRepository(VicidialUser::class)->find($data['userId']);
             if (!$user) {
                 return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_BAD_REQUEST);
             }
     
             // Vérifier format date
             try {
                 $startTime = new \DateTime($data['startTime']);
                 $endTime = new \DateTime($data['endTime']);
             } catch (\Exception $e) {
                 return $this->json(['error' => 'Format de date invalide', 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
             }
     
             // Mettre à jour
             $this->appointmentService->updateAppointment(
                 $appointment,
                 $startTime,
                 $endTime,
                 $data['description'],
                 $lead,
                 $user
             );
     
             // ✅ Recharger l'objet complet après update
             $appointment = $this->entityManager->getRepository(Appointment::class)->find($appointment->getId());
     
             // ✅ Sérialiser avec groupe
             return new JsonResponse(
                 $this->serializer->serialize($appointment, 'json', ['groups' => 'appointment:read']),
                 Response::HTTP_OK,
                 [],
                 true
             );
     
         } catch (\Throwable $e) {
             error_log("💥 Erreur update : " . $e->getMessage());
             error_log("Trace : " . $e->getTraceAsString());
     
             return $this->json([
                 'error' => 'Erreur interne',
                 'message' => $e->getMessage()
             ], Response::HTTP_INTERNAL_SERVER_ERROR);
         }
     }
     
     #[Route('', name: 'appointment_list_all', methods: ['GET'])]
     public function listAll(): JsonResponse
     {
         $appointments = $this->appointmentService->getAllAppointments();
     
         $data = array_map(fn(Appointment $a) => [
             'id' => $a->getId(),
             'startTime' => $a->getStartTime()?->format('Y-m-d H:i:s'),
             'endTime' => $a->getEndTime()?->format('Y-m-d H:i:s'),
             'description' => $a->getDescription(),
     
             // User null-safe
             'user' => $a->getUser() ? [
                 'id' => $a->getUser()->getId(),
                 'username' => $a->getUser()->getUser(),
                 'fullName' => $a->getUser()->getFullName()
             ] : null,
     
             // Lead null-safe
             'lead' => $a->getLead() ? [
                 'id' => $a->getLead()->getId(),
                 'firstName' => $a->getLead()->getFirstName(),
                 'lastName' => $a->getLead()->getLastName(),
             ] : null,
     
             // Notes null-safe
             'notes' => $a->getNote() ? array_map(fn($n) => [
                 'id' => $n->getId(),
                 'content' => $n->getContent(),
                 'createdAt' => $n->getCreatedAt()?->format('Y-m-d H:i:s')
             ], $a->getNote()->toArray()) : [],
     
             // Tasks null-safe
             'tasks' => $a->getTasks() ? array_map(fn($t) => [
                 'id' => $t->getId(),
                 'title' => $t->getTitle(),
                 'dueDate' => $t->getDueDate()?->format('Y-m-d H:i:s')
             ], $a->getTasks()->toArray()) : [],
             
         ], $appointments);
     
         return $this->json($data);
     }
     
    /**
     * Supprime un rendez-vous
     */


     #[Route('/{id}', name: 'appointment_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
     public function delete(Appointment $appointment, Request $request, EntityManagerInterface $em): JsonResponse
     {
         $deleteTasks = filter_var($request->query->get('deleteTasks'), FILTER_VALIDATE_BOOLEAN);
     
         try {
             // Supprimer les notes liées
             foreach ($appointment->getNote() as $note) {
                 $em->remove($note);
             }
     
             // Supprimer les tâches si demandé
             if ($deleteTasks) {
                 foreach ($appointment->getTasks() as $task) {
                     $em->remove($task);
                 }
             } else {
                 // Si tâches existent et deleteTasks=false → bloquer suppression
                 if (count($appointment->getTasks()) > 0) {
                     return $this->json([
                         'error' => 'Impossible de supprimer le rendez-vous : des tâches sont associées.'
                     ], 400);
                 }
             }
     
             // Enfin supprimer le rendez-vous
             $em->remove($appointment);
             $em->flush();
     
             return $this->json([
                 'message' => "Rendez-vous supprimé avec succès"
             ]);
     
         } catch (\Exception $e) {
             return $this->json([
                 'error' => 'Erreur serveur lors de la suppression : ' . $e->getMessage()
             ], 500);
         }
     }
     
    /**
     * Liste les rendez-vous d'un utilisateur
     */
   /* #[Route('/user/{userId}', name: 'appointment_list_user', methods: ['GET'])]
    public function listForUser (int $userId): JsonResponse
    {
        try {
            $user = $this->entityManager->getRepository(VicidialUser ::class)->find($userId);
            
            if (!$user) {
                return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
            }

            $appointments = $this->appointmentService->getUpcomingAppointments($user);

            return new JsonResponse(
                $this->serializer->serialize($appointments, 'json', ['groups' => 'appointment:read']),
                Response::HTTP_OK,
                [],
                true
            );
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }*/
   /* #[Route('/user/{username}', name: 'appointment_list_user', methods: ['GET'])]
    public function listForUsername(string $username): JsonResponse
    {
        $user = $this->entityManager->getRepository(VicidialUser::class)
                                   ->findOneBy(['user' => $username]);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        try {
            $appointments = $this->appointmentService->getUpcomingAppointments($user);

            return $this->json($appointments, Response::HTTP_OK, [], ['groups' => 'appointment:read']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }*/
/*#[Route('/user/{username}', name: 'appointment_list_user', methods: ['GET'])]
public function listForUsername(string $username): JsonResponse
{
    try {
        // Récupération de l'utilisateur
        $user = $this->entityManager->getRepository(VicidialUser::class)
                                   ->findOneBy(['user' => $username]);

        if (!$user) {
            return $this->json([
                'error' => 'Utilisateur non trouvé',
                'username' => $username
            ], Response::HTTP_NOT_FOUND);
        }

        // Récupération des rendez-vous
        $appointments = $this->appointmentService->getUpcomingAppointments($user);

        // Vérifier si le résultat est vide
        if (!$appointments) {
            return $this->json([], Response::HTTP_OK);
        }

        // Retour JSON avec groupe de sérialisation
        return $this->json(
            $appointments,
            Response::HTTP_OK,
            [],
            ['groups' => 'appointment:read']
        );
    } catch (\Throwable $e) {
        // Afficher l'erreur complète pour debug
        return $this->json([
            'error' => 'Erreur interne',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}



    /*#[Route('/user/{username}', name: 'appointment_list_user', methods: ['GET'])]
    public function listForUsername(string $username): JsonResponse
    {
        try {
            $user = $this->entityManager->getRepository(VicidialUser::class)
                                       ->findOneBy(['user' => $username]);

            if (!$user) {
                return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
            }

            $appointments = $this->appointmentService->getUpcomingAppointments($user) ?? [];

            return $this->json($appointments, Response::HTTP_OK, [], ['groups' => 'appointment:read']);
        } catch (\Throwable $e) {
            // Utilise le logger injecté
            $this->logger->error('Erreur listForUsername: ' . $e->getMessage());

            return $this->json([
                'error' => 'Erreur interne',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }*/

    #[Route('/user/{username}', name: 'appointment_list_user', methods: ['GET'])]
    public function listForUsername(string $username): JsonResponse
    {
        try {
            // Récupération de l'utilisateur par username
            $user = $this->entityManager
                         ->getRepository(VicidialUser::class)
                         ->findOneBy(['user' => $username]);

            if (!$user) {
                return $this->json([
                    'error' => 'Utilisateur non trouvé',
                    'username' => $username
                ], Response::HTTP_NOT_FOUND);
            }

            // Récupération des rendez-vous à venir
            $appointments = $this->appointmentService->getUpcomingAppointments($user);

            // Sérialisation sécurisée pour éviter les boucles
            $data = [];
            foreach ($appointments as $appointment) {
                $data[] = [
                    'id' => $appointment->getId(),
                    'startTime' => $appointment->getStartTime()?->format('Y-m-d H:i:s'),
                    'endTime' => $appointment->getEndTime()?->format('Y-m-d H:i:s'),
                    'description' => $appointment->getDescription(),
                    'leadId' => $appointment->getLead()?->getId(),
                    // Si tu veux renvoyer aussi l'id utilisateur
                    'userId' => $appointment->getUser()?->getId(),
                ];
            }

            return $this->json($data, Response::HTTP_OK);
        } catch (\Throwable $e) {
            // Gestion des erreurs pour ne jamais renvoyer une boucle infinie
            return $this->json([
                'error' => 'Erreur interne',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
  
    
    #[Route('/userapp/{id}', name: 'appointments_by_user', methods: ['GET'])]
    public function getAppointmentsByUser(
        int $id,
        AppointmentRepository $appointmentRepository,
        VicidialUserRepository $userRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        // ✅ Vérifier si l'utilisateur existe
        $user = $userRepository->find($id);
    
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé', 'userId' => $id], 404);
        }
    
        // ✅ Récupérer les rendez-vous de l’utilisateur
        $appointments = $appointmentRepository->findByUser($user);
    
        // ✅ Sérialiser les rendez-vous sans boucle infinie
        $json = $serializer->serialize(
            $appointments,
            'json',
            ['groups' => ['appointment:read']]
        );
    
        return new JsonResponse($json, 200, [], true);
    }
    #[Route('/{id}', name: 'appointment_get', methods: ['GET'])] 
    public function getAppointment(int $id, AppointmentRepository $appointmentRepository, SerializerInterface $serializer): JsonResponse { $appointment = $appointmentRepository->find($id); 
        if (!$appointment) { return $this->json(['error' => 'Rendez-vous non trouvé'], Response::HTTP_NOT_FOUND); } 
        $json = $serializer->serialize($appointment, 'json', ['groups' => ['appointment:read']]); 
        return new JsonResponse($json, Response::HTTP_OK, [], true); } }    
    
