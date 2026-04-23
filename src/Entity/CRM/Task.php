<?php

namespace App\Entity\CRM;

use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: "tasks")]
#[ORM\HasLifecycleCallbacks]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    #[Groups(['task:read', 'appointment:read'])]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Groups(['task:read', 'appointment:read'])]
    private ?string $title = null;

    #[ORM\Column(type: "text", nullable: true)]
    #[Groups(['task:read', 'appointment:read'])]
    private ?string $description = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    #[Groups(['task:read', 'appointment:read'])]
    private ?\DateTime $dueDate = null;

    #[ORM\Column(type: "boolean")]
    #[Groups(['task:read', 'appointment:read'])]
    private bool $completed = false;

    #[ORM\Column(type: "datetime")]
    #[Groups(['task:read', 'appointment:read'])]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    #[Groups(['task:read', 'appointment:read'])]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column(enumType: TaskStatus::class)]
    #[Groups(['task:read', 'appointment:read'])]
    private TaskStatus $status = TaskStatus::PENDING;

    #[ORM\Column(enumType: TaskPriority::class)]
    #[Groups(['task:read', 'appointment:read'])]
    private TaskPriority $priority = TaskPriority::MEDIUM;

    #[ORM\Column(name: "vicidial_user", type: "string", length: 50, nullable: true)]
    #[Groups(['task:read', 'appointment:read'])]
    private ?string $vicidialUser = null;

    #[ORM\ManyToOne(inversedBy: "tasks")]
    #[ORM\JoinColumn(name: "appointment_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    #[Groups(['task:read'])]
    private ?Appointment $appointment = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }

        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
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

    public function setDueDate(?\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate ? \DateTime::createFromInterface($dueDate) : null;
        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function getCompleted(): bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): static
    {
        $this->completed = $completed;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = \DateTime::createFromInterface($createdAt);
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt ? \DateTime::createFromInterface($updatedAt) : null;
        return $this;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function setStatus(TaskStatus $status): static
    {
        $this->status = $status;
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

    public function getVicidialUser(): ?string
    {
        return $this->vicidialUser;
    }

    public function setVicidialUser(?string $vicidialUser): static
    {
        $this->vicidialUser = $vicidialUser;
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