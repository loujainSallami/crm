<?php

namespace App\Service;

use App\Entity\CrmUser;
use App\Entity\Task;
use App\Entity\VicidialUser;
use App\Entity\Appointment;
use App\Enum\TaskStatus;
use App\Enum\TaskPriority;
use Doctrine\ORM\EntityManagerInterface;

class TaskService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function createTask(
        string $title,
        ?string $description,
        ?\DateTimeInterface $dueDate,
        CrmUser $user,
        Appointment $appointment,
        TaskStatus $status = TaskStatus::PENDING,
        TaskPriority $priority = TaskPriority::MEDIUM
    ): Task {
        $task = (new Task())
            ->setTitle($title)
            ->setDescription($description)
            ->setDueDate($dueDate)
            ->setUser($user)
            ->setAppointment($appointment)
            ->setStatus($status)
            ->setPriority($priority)
            ->setCreatedAt(new \DateTime())
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
        ?bool $completed = null
    ): Task {
        if ($title !== null) $task->setTitle($title);
        if ($description !== null) $task->setDescription($description);
        if ($dueDate !== null) $task->setDueDate($dueDate);
        if ($status !== null) $task->setStatus($status);
        if ($priority !== null) $task->setPriority($priority);
        if ($completed !== null) $task->setCompleted($completed);

        $this->entityManager->flush();

        return $task;
    }

    public function deleteTask(Task $task): void
    {
        $this->entityManager->remove($task);
        $this->entityManager->flush();
    }

    public function getUserTasks(CrmUser $user): array
    {
        return $this->entityManager
            ->getRepository(Task::class)
            ->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }

    public function getTasksByAppointment(Appointment $appointment): array
    {
        return $this->entityManager
            ->getRepository(Task::class)
            ->findBy(['appointment' => $appointment], ['createdAt' => 'DESC']);
    }

    public function getAllTasks(): array
    {
        return $this->entityManager->getRepository(Task::class)->findAll();
    }
}
