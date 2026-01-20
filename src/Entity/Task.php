<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use App\Enum\TaskStatus;     
use App\Enum\TaskPriority; 
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
   #[ORM\Column]
    #[Groups(groups: ['task:read'])]

    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(groups: ['task:read'])]

    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(groups: ['task:read'])]

    private ?string $description = null;

    // PROPRIÉTÉS MANQUANTES
    #[ORM\Column(type: 'boolean')]
    #[Groups(groups: ['task:read'])]

    private bool $completed = false;

    #[ORM\Column]
    #[Groups(groups: ['task:read'])]

    private ?\DateTime $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(groups: ['task:read'])]
    private ?\DateTime $completedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(groups: ['task:read'])]

    private ?\DateTime $dueDate = null;

    // ENUMS
    #[ORM\Column(type: 'string', enumType: TaskStatus::class)]
    #[Groups(groups: ['task:read'])]

    private TaskStatus $status = TaskStatus::PENDING;

    #[ORM\Column(type: 'string', enumType: TaskPriority::class)]
    #[Groups(groups: ['task:read'])]

    private TaskPriority $priority = TaskPriority::MEDIUM;
    #[Groups(groups: ['task:read'])]

    #[ORM\ManyToOne(inversedBy: 'tasks')]

    #[ORM\JoinColumn(name: "vicidial_user_id", referencedColumnName: "user_id", nullable: false)]
    private ?CrmUser $user = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[Groups(groups: ['task:read'])]


    #[ORM\JoinColumn(nullable: false)]
    private ?Appointment $appointment = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // GETTERS/SETTERS POUR LES NOUVELLES PROPRIÉTÉS

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function setStatus(TaskStatus $status): static
    {
        $this->status = $status;
        
        // Mettre à jour automatiquement completed si status est COMPLETED
        if ($status === TaskStatus::COMPLETED && !$this->completedAt) {
            $this->completedAt = new \DateTime();
            $this->completed = true;
        }
        
        return $this;
    }

    public function getPriority(): TaskPriority
    {
        return $this->priority;
    }

    public function setPriority(TaskPriority $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): static
    {
        $this->completed = $completed;
        
        if ($completed && !$this->completedAt) {
            $this->completedAt = new \DateTime();
            $this->status = TaskStatus::COMPLETED;
        } elseif (!$completed) {
            $this->completedAt = null;
            if ($this->status === TaskStatus::COMPLETED) {
                $this->status = TaskStatus::PENDING;
            }
        }
        
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTime
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTime $completedAt): static
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    // MÉTHODES UTILITAIRES
    #[Groups(['task:read'])]
    public function isOverdue(): bool
    {
        if (!$this->dueDate || $this->status === TaskStatus::COMPLETED) {
            return false;
        }
        
        return $this->dueDate < new \DateTime();
    }
    #[Groups(['task:read'])]
    public function getDaysUntilDue(): ?int
    {
        if (!$this->dueDate) {
            return null;
        }
        
        $now = new \DateTime();
        $interval = $now->diff($this->dueDate);
        
        return (int) $interval->format('%r%a');
    }

    // GETTERS/SETTERS EXISTANTS (à garder)

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDueDate(): ?\DateTime
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTime $dueDate): static
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getUser(): ?CrmUser
    {
        return $this->user;
    }

    public function setUser(?CrmUser $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getAppointment(): ?Appointment
    {
        return $this->appointment;
    }

    public function setAppointment(?Appointment $appointment): static
    {
        $this->appointment = $appointment;
        return $this;
    }
}