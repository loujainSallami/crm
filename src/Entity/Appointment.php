<?php

namespace App\Entity;
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
    #[Groups(['appointment:read'])]
    #[ORM\Column(name: 'start_time', type: 'datetime')]
    private ?\DateTimeInterface $startTime = null;
    
    #[ORM\Column(name: 'end_time', type: 'datetime')]
    private ?\DateTimeInterface $endTime = null;
    #[ORM\Column(length: 255)]
    private ?string $description = null;
    #[Groups(['appointment:read'])]

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "user_id", nullable: false)]
        private ?CrmUser $user = null;

        #[ORM\ManyToOne(inversedBy: 'appointments')]
        #[ORM\JoinColumn(name: "vicidial_lead_id", referencedColumnName: "id", nullable: true)]
        private ?CrmLead $lead = null;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'appointment')]
    #[Groups(['appointment:read'])]
    private Collection $Note;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'appointment')]
    private Collection $notifications;

    /**
     * @var Collection<int, Task>
     */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'appointment')]
    #[Groups(['appointment:read'])]

    private Collection $tasks;

    public function __construct()
    {
        $this->Note = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartTime(): ?\DateTime
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTime $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTime
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTime $endTime): static
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

    public function getUser(): ?CrmUser
    {
        return $this->user;
    }

    public function setUser(?CrmUser $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getLead(): ?CrmLead
    {
        return $this->lead;
    }
    
    public function setLead(?CrmLead $lead): static
    {
        $this->lead = $lead;
        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNote(): Collection
    {
        return $this->Note;
    }

    public function addNote(Note $note): static
    {
        if (!$this->Note->contains($note)) {
            $this->Note->add($note);
            $note->setAppointment($this);
        }

        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->Note->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getAppointment() === $this) {
                $note->setAppointment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
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
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getAppointment() === $this) {
                $notification->setAppointment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Task>
     */
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
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getAppointment() === $this) {
                $task->setAppointment(null);
            }
        }

        return $this;
    }

}
