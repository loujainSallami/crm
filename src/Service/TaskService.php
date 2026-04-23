<?php

namespace App\Service;

use App\Entity\CRM\Task;
use App\Entity\CRM\Appointment;
use App\Enum\TaskStatus;
use App\Enum\TaskPriority;
use Doctrine\ORM\EntityManagerInterface;

class TaskService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createTask(
        string $title,
        ?string $description,
        ?\DateTimeInterface $dueDate,
        ?string $vicidialUser,
        Appointment $appointment,
        TaskStatus $status = TaskStatus::PENDING,
        TaskPriority $priority = TaskPriority::MEDIUM
    ): Task {
        $task = (new Task())
            ->setTitle($title)
            ->setDescription($description)
            ->setDueDate($dueDate)
            ->setVicidialUser($vicidialUser)
            ->setAppointment($appointment)
            ->setStatus($status)
            ->setPriority($priority)
            ->setCompleted($status === TaskStatus::COMPLETED);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    public function updateTask(
        Task $task,
        ?string $title = null,
        ?string $description = null,
        ?\DateTimeInterface $dueDate = null,
        ?TaskStatus $status = null,
        ?TaskPriority $priority = null,
        ?bool $completed = null,
        ?string $vicidialUser = null
    ): Task {
        if ($title !== null) {
            $task->setTitle($title);
        }

        if ($description !== null) {
            $task->setDescription($description);
        }

        if ($dueDate !== null) {
            $task->setDueDate($dueDate);
        }

        if ($status !== null) {
            $task->setStatus($status);
        }

        if ($priority !== null) {
            $task->setPriority($priority);
        }

        if ($completed !== null) {
            $task->setCompleted($completed);
        }

        if ($vicidialUser !== null) {
            $task->setVicidialUser($vicidialUser);
        }

        if ($status === TaskStatus::COMPLETED) {
            $task->setCompleted(true);
        }

        $this->entityManager->flush();

        return $task;
    }

    public function deleteTask(Task $task): void
    {
        $this->entityManager->remove($task);
        $this->entityManager->flush();
    }

    public function getUserTasks(string $vicidialUser): array
    {
        return $this->entityManager
            ->getRepository(Task::class)
            ->findBy(['vicidialUser' => $vicidialUser], ['createdAt' => 'DESC']);
    }

    public function getTasksByAppointment(Appointment $appointment): array
    {
        return $this->entityManager
            ->getRepository(Task::class)
            ->findBy(['appointment' => $appointment], ['createdAt' => 'DESC']);
    }

    public function getAllTasks(): array
    {
        return $this->entityManager
            ->getRepository(Task::class)
            ->findAll();
    }
}