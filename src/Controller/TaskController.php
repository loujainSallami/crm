<?php

namespace App\Controller;

use App\Entity\CRM\Task;
use App\Entity\CRM\Appointment;
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
        private readonly TaskService $taskService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'task_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'error' => 'JSON invalide'
            ], Response::HTTP_BAD_REQUEST);
        }

        $vicidialUser = $data['vicidialUser'] ?? null;

        if (!$vicidialUser) {
            return $this->json([
                'error' => 'Aucun vicidialUser fourni',
                'vicidialUser' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $appointmentId = $data['appointmentId'] ?? null;
        if (!$appointmentId) {
            return $this->json([
                'error' => 'Aucun ID de rendez-vous fourni',
                'appointment_id' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $appointment = $this->entityManager
            ->getRepository(Appointment::class)
            ->find($appointmentId);

        if (!$appointment) {
            return $this->json([
                'error' => 'Rendez-vous introuvable',
                'appointment_id' => $appointmentId
            ], Response::HTTP_NOT_FOUND);
        }

        $statusInput = $data['status'] ?? null;
        $priorityInput = $data['priority'] ?? null;

        $status = TaskStatus::PENDING;
        if ($statusInput) {
            $statusUpper = strtoupper((string) $statusInput);
            $matched = null;

            foreach (TaskStatus::cases() as $case) {
                if ($case->name === $statusUpper) {
                    $matched = $case;
                    break;
                }
            }

            if (!$matched) {
                return $this->json([
                    'error' => 'Status invalide',
                    'status_received' => $statusInput,
                    'status_enum_values' => array_map(fn($s) => $s->name, TaskStatus::cases())
                ], Response::HTTP_BAD_REQUEST);
            }

            $status = $matched;
        }

        $priority = TaskPriority::MEDIUM;
        if ($priorityInput) {
            $priorityUpper = strtoupper((string) $priorityInput);
            $matched = null;

            foreach (TaskPriority::cases() as $case) {
                if ($case->name === $priorityUpper) {
                    $matched = $case;
                    break;
                }
            }

            if (!$matched) {
                return $this->json([
                    'error' => 'Priority invalide',
                    'priority_received' => $priorityInput,
                    'priority_enum_values' => array_map(fn($p) => $p->name, TaskPriority::cases())
                ], Response::HTTP_BAD_REQUEST);
            }

            $priority = $matched;
        }

        try {
            $dueDate = isset($data['dueDate']) && $data['dueDate'] !== ''
                ? new \DateTime($data['dueDate'])
                : null;
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Format de dueDate invalide'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $task = $this->taskService->createTask(
                $data['title'] ?? '',
                $data['description'] ?? null,
                $dueDate,
                $vicidialUser,
                $appointment,
                $status,
                $priority
            );

            return $this->json(
                $task,
                Response::HTTP_CREATED,
                [],
                ['groups' => ['task:read']]
            );
        } catch (\Throwable $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('', name: 'task_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $tasks = $this->taskService->getAllTasks();

        return $this->json(
            $tasks,
            Response::HTTP_OK,
            [],
            ['groups' => ['task:read']]
        );
    }

    #[Route('/appointment/{id}', name: 'tasks_by_appointment', methods: ['GET'])]
    public function getTasksByAppointment(Appointment $appointment): JsonResponse
    {
        $tasks = $this->taskService->getTasksByAppointment($appointment);

        return $this->json(
            $tasks,
            Response::HTTP_OK,
            [],
            ['groups' => ['task:read']]
        );
    }

    #[Route('/user/{vicidialUser}', name: 'tasks_by_vicidial_user', methods: ['GET'])]
    public function getTasksByVicidialUser(string $vicidialUser): JsonResponse
    {
        $tasks = $this->taskService->getUserTasks($vicidialUser);

        return $this->json(
            $tasks,
            Response::HTTP_OK,
            [],
            ['groups' => ['task:read']]
        );
    }

    #[Route('/{id}', name: 'task_update', methods: ['PUT'])]
    public function update(Task $task, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'error' => 'JSON invalide'
            ], Response::HTTP_BAD_REQUEST);
        }

        $status = null;
        if (isset($data['status'])) {
            $statusUpper = strtoupper((string) $data['status']);

            foreach (TaskStatus::cases() as $case) {
                if ($case->name === $statusUpper) {
                    $status = $case;
                    break;
                }
            }

            if (!$status) {
                return $this->json([
                    'error' => 'Status invalide',
                    'status_received' => $data['status'],
                    'status_enum_values' => array_map(fn($s) => $s->name, TaskStatus::cases())
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $priority = null;
        if (isset($data['priority'])) {
            $priorityUpper = strtoupper((string) $data['priority']);

            foreach (TaskPriority::cases() as $case) {
                if ($case->name === $priorityUpper) {
                    $priority = $case;
                    break;
                }
            }

            if (!$priority) {
                return $this->json([
                    'error' => 'Priority invalide',
                    'priority_received' => $data['priority'],
                    'priority_enum_values' => array_map(fn($p) => $p->name, TaskPriority::cases())
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            $dueDate = isset($data['dueDate']) && $data['dueDate'] !== ''
                ? new \DateTime($data['dueDate'])
                : null;
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Format de dueDate invalide'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $updatedTask = $this->taskService->updateTask(
                $task,
                $data['title'] ?? null,
                $data['description'] ?? null,
                $dueDate,
                $status,
                $priority,
                $data['completed'] ?? null,
                $data['vicidialUser'] ?? null
            );

            return $this->json(
                $updatedTask,
                Response::HTTP_OK,
                [],
                ['groups' => ['task:read']]
            );
        } catch (\Throwable $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'task_delete', methods: ['DELETE'])]
    public function delete(Task $task): JsonResponse
    {
        try {
            $this->taskService->deleteTask($task);

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}