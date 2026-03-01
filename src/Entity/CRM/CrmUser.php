<?php

namespace App\Entity\CRM;

use App\Repository\CrmUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: CrmUserRepository::class)]
#[ORM\Table(name: "crm_users")]
class CrmUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private ?int $user_id = null;

    #[ORM\Column(type: "string", length: 255, unique: true)]
    private ?string $username = null;

    // mot de passe hashé
    #[ORM\Column(type: "string", length: 255)]
    private ?string $pass = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $full_name = null;

    #[ORM\Column(type: "smallint", nullable: true)]
    private ?int $user_level = 1;

    // ================= Relations =================

    #[ORM\OneToMany(mappedBy: 'crmUser', targetEntity: Appointment::class)]
    private Collection $appointments;

    #[ORM\OneToMany(mappedBy: 'crmUser', targetEntity: Task::class)]
    private Collection $tasks;

    #[ORM\OneToMany(mappedBy: 'crmUser', targetEntity: Notification::class)]
    private Collection $notifications;

    #[ORM\OneToMany(mappedBy: 'crmUser', targetEntity: Note::class)]
    private Collection $notes;

    public function __construct()
    {
        $this->appointments = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->notes = new ArrayCollection();
    }

    // ================= Getters/Setters =================

    public function getId(): ?int
    {
        return $this->user_id;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    // Symfony Security utilise getPassword() via PasswordAuthenticatedUserInterface
    public function getPassword(): ?string
    {
        return $this->pass;
    }

    public function setPassword(string $pass): static
    {
        $this->pass = $pass;
        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->full_name;
    }

    public function setFullName(?string $full_name): static
    {
        $this->full_name = $full_name;
        return $this;
    }

    public function getUserLevel(): ?int
    {
        return $this->user_level;
    }

    public function setUserLevel(?int $user_level): static
    {
        $this->user_level = $user_level;
        return $this;
    }

    // ================= Security =================

    public function getUserIdentifier(): string
    {
        return $this->username ?? '';
    }

    public function getRoles(): array
    {
        // Toujours au moins ROLE_USER
        $roles = ['ROLE_USER'];

        if ($this->user_level !== null && $this->user_level >= 9) {
            $roles[] = 'ROLE_ADMIN';
        }

        return array_values(array_unique($roles));
    }

    public function eraseCredentials(): void
    {
        // rien à effacer (si tu stockes des champs temporaires, efface-les ici)
    }

    // ================= Relations helpers =================

    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): static
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments->add($appointment);
            $appointment->setCrmUser($this);
        }
        return $this;
    }

    public function removeAppointment(Appointment $appointment): static
    {
        if ($this->appointments->removeElement($appointment)) {
            if ($appointment->getCrmUser() === $this) {
                $appointment->setCrmUser(null);
            }
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
            $task->setUser($this);
        }
        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            if ($task->getUser() === $this) {
                $task->setUser(null);
            }
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
            $notification->setCrmUser($this);
        }
        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            if ($notification->getCrmUser() === $this) {
                $notification->setCrmUser(null);
            }
        }
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
            $note->setCrmUser($this);
        }
        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            if ($note->getCrmUser() === $this) {
                $note->setCrmUser(null);
            }
        }
        return $this;
    }
}