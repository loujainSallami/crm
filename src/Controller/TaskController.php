<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Appointment;
use App\Entity\VicidialUser;
use App\Enum\TaskStatus;
use App\Enum\TaskPriority;
use App\Service\TaskService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/tasks')]
class TaskController extends AbstractController
{
    public function __construct(
        private TaskService $taskService,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * 🔹 Créer une nouvelle tâche
     */
    #[Route('', name: 'task_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        dump($data); // Debug body JSON

      // Récupérer l'agent qui crée le rendez-vous
$userId = $data['userId'] ?? null;

if (!$userId) {
    return $this->json([
        'error' => 'Aucun ID d\'utilisateur fourni',
        'user_id' => null
    ], Response::HTTP_BAD_REQUEST);
}

// Chercher l’agent par son ID
$user = $this->entityManager->getRepository(VicidialUser::class)->find($userId);

if (!$user) {
    return $this->json([
        'error' => 'Utilisateur introuvable',
        'user_id' => $userId
    ], Response::HTTP_NOT_FOUND);
}


        // Récupérer l'ID du rendez-vous
        $appointmentId = $data['appointmentId'] ?? null;
        if (!$appointmentId) {
            return $this->json([
                'error' => 'Aucun ID de rendez-vous fourni',
                'appointment_id' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $appointment = $this->entityManager->getRepository(Appointment::class)->find($appointmentId);
        if (!$appointment) {
            return $this->json([
                'error' => 'Rendez-vous introuvable',
                'appointment_id' => $appointmentId,
                'message' => 'Vérifiez que cet ID existe bien dans la base de données'
            ], Response::HTTP_NOT_FOUND);
        }

        // Gestion des enums avec validation manuelle (pour enums purs)
        $statusInput = $data['status'] ?? null;
        $priorityInput = $data['priority'] ?? null;

        $status = TaskStatus::PENDING;
        if ($statusInput) {
            $statusUpper = strtoupper($statusInput);
            foreach (TaskStatus::cases() as $case) {
                if ($case->name === $statusUpper) {
                    $status = $case;
                    break;
                }
            }
        }

        $priority = TaskPriority::MEDIUM;
        if ($priorityInput) {
            $priorityUpper = strtoupper($priorityInput);
            foreach (TaskPriority::cases() as $case) {
                if ($case->name === $priorityUpper) {
                    $priority = $case;
                    break;
                }
            }
        }

        // Vérifier si les valeurs fournies étaient invalides
        $statusValid = true;
        if ($statusInput && $status === TaskStatus::PENDING && strtoupper($statusInput) !== 'PENDING') {
            $statusValid = false;
        }
        $priorityValid = true;
        if ($priorityInput && $priority === TaskPriority::MEDIUM && strtoupper($priorityInput) !== 'MEDIUM') {
            $priorityValid = false;
        }

        if (!$statusValid || !$priorityValid) {
            return $this->json([
                'error' => 'Status ou priority invalide',
                'status_received' => $statusInput,
                'priority_received' => $priorityInput,
                'status_enum_values' => array_map(fn($s) => $s->name, TaskStatus::cases()),
                'priority_enum_values' => array_map(fn($p) => $p->name, TaskPriority::cases())
            ], Response::HTTP_BAD_REQUEST);
        }

        // Créer la tâche
        try {
            $task = $this->taskService->createTask(
                $data['title'] ?? '',
                $data['description'] ?? null,
                isset($data['dueDate']) ? new \DateTime($data['dueDate']) : null,
                $user,
                $appointment,
                $status,
                $priority
            );

            return $this->json($task, Response::HTTP_CREATED, [], ['groups' => ['task:read']]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * 🔹 Lister toutes les tâches
     */
    #[Route('', name: 'task_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $tasks = $this->taskService->getAllTasks();
        return $this->json($tasks, Response::HTTP_OK, [], ['groups' => ['task:read']]);
    }

    /**
     * 🔹 Récupérer les tâches d’un rendez-vous
     */
    #[Route('/appointment/{id}', name: 'tasks_by_appointment', methods: ['GET'])]
    public function getTasksByAppointment(Appointment $appointment): JsonResponse
    {
        $tasks = $this->taskService->getTasksByAppointment($appointment);
        return $this->json($tasks, Response::HTTP_OK, [], ['groups' => ['task:read']]);
    }

    /**
     * 🔹 Mettre à jour une tâche
     */
    #[Route('/{id}', name: 'task_update', methods: ['PUT'])]
    public function update(Task $task, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        dump($data);

        $status = null;
        if (isset($data['status'])) {
            $statusUpper = strtoupper($data['status']);
            foreach (TaskStatus::cases() as $case) {
                if ($case->name === $statusUpper) {
                    $status = $case;
                    break;
                }
            }
        }

        $priority = null;
        if (isset($data['priority'])) {
            $priorityUpper = strtoupper($data['priority']);
            foreach (TaskPriority::cases() as $case) {
                if ($case->name === $priorityUpper) {
                    $priority = $case;
                    break;
                }
            }
        }

        if ((isset($data['status']) && !$status) || (isset($data['priority']) && !$priority)) {
            return $this->json([
                'error' => 'Status ou priority invalide',
                'status_received' => $data['status'] ?? null,
                'priority_received' => $data['priority'] ?? null,
                'status_enum_values' => array_map(fn($s) => $s->name, TaskStatus::cases()),
                'priority_enum_values' => array_map(fn($p) => $p->name, TaskPriority::cases())
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $updatedTask = $this->taskService->updateTask(
                $task,
                $data['title'] ?? null,
                $data['description'] ?? null,
                isset($data['dueDate']) ? new \DateTime($data['dueDate']) : null,
                $status,
                $priority,
                $data['completed'] ?? null
            );

            return $this->json($updatedTask, Response::HTTP_OK, [], ['groups' => ['task:read']]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * 🔹 Supprimer une tâche
     */
    #[Route('/{id}', name: 'task_delete', methods: ['DELETE'])]
    public function delete(Task $task): JsonResponse
    {
        try {
            $this->taskService->deleteTask($task);
            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
