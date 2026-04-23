<?php

namespace App\Entity\CRM;

use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\AppointmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column]
    #[Groups(['appointment:read'])]
    private ?int $id = null;

    #[ORM\Column(name: 'start_time', type: 'datetime')]
    #[Groups(['appointment:read'])]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(name: 'end_time', type: 'datetime')]
    #[Groups(['appointment:read'])]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(length: 255)]
    #[Groups(['appointment:read'])]
    private ?string $description = '';

    #[ORM\Column(name: "vicidial_user", type: "string", length: 50, nullable: false)]
    #[Groups(['appointment:read'])]
    private ?string $vicidialUser = null;

    #[ORM\Column(name: "vicidial_lead_id", type: "integer", nullable: true)]
    #[Groups(['appointment:read'])]
    private ?int $vicidialLeadId = null;

    #[ORM\Column(name: "vicidial_campaign_id", type: "string", length: 20, nullable: true)]
    #[Groups(['appointment:read'])]
    private ?string $vicidialCampaignId = null;

    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'appointment')]
    #[Groups(['appointment:read'])]
    private Collection $notes;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'appointment')]
    private Collection $notifications;

    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'appointment')]
    #[Groups(['appointment:read'])]
    private Collection $tasks;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
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

    public function getVicidialLeadId(): ?int
    {
        return $this->vicidialLeadId;
    }

    public function setVicidialLeadId(?int $vicidialLeadId): static
    {
        $this->vicidialLeadId = $vicidialLeadId;
        return $this;
    }

    public function getVicidialCampaignId(): ?string
    {
        return $this->vicidialCampaignId;
    }

    public function setVicidialCampaignId(?string $vicidialCampaignId): static
    {
        $this->vicidialCampaignId = $vicidialCampaignId;
        return $this;
    }

    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setAppointment($this);
        }

        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note) && $note->getAppointment() === $this) {
            $note->setAppointment(null);
        }

        return $this;
    }

    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setAppointment($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification) && $notification->getAppointment() === $this) {
            $notification->setAppointment(null);
        }

        return $this;
    }

    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setAppointment($this);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task) && $task->getAppointment() === $this) {
            $task->setAppointment(null);
        }

        return $this;
    }
}