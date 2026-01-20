<?php

namespace App\Entity;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\VicidialUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
#[ORM\Entity(repositoryClass: VicidialUserRepository::class)]
#[ORM\Table(name: "crm_users")]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USER', fields: ['user'])]
#[UniqueEntity(fields: ['user'], message: 'There is already an account with this user')]
class CrmUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]

    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    #[Groups(['appointment:read', 'appointment:write'])]

    private ?int $user_id = null;

    #[ORM\Column(name: '"user"', type: 'string', length: 255, unique: true)]
        #[Groups(['appointment:read', 'appointment:write'])]

    private ?string $user = null;

    #[ORM\Column(name: 'pass', type: 'string', length: 255)]
        private ?string $pass = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    #[Groups(['appointment:read', 'appointment:write'])]


    private ?string $full_name = null;

    #[ORM\Column(type: "smallint", options: ["unsigned" => true], nullable: true)]
    #[Groups(['appointment:read'])]

    private ?int $user_level = 1;

   
    /**
     * @var Collection<int, Appointment>
     */
    #[ORM\OneToMany(targetEntity: Appointment::class, mappedBy: 'user')]
    #[Groups(['user:read'])]
    private Collection $appointments;
    /**
     * @var Collection<int, Task>
     */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'user')]
    private Collection $tasks;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user')]
    private Collection $notifications;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'user')]
    private Collection $notes;

    public function __construct()
    {
        $this->appointments = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->notes = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->user_id;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(string $user): static
    {
        $this->user = $user;
        return $this;
    }

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






  

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        // Assumes the user level defines their role
        $roles = [];
        if ($this->user_level >= 9) {
            $roles[] = 'ROLE_ADMIN';
        } else {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->user;
    }
        
        
      
    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function eraseCredentials(): void
    {
        // No sensitive data to clear
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function getPass(): ?string
    {
        return $this->pass;
    }

    public function setPass(string $pass): static
    {
        $this->pass = $pass;

        return $this;
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): static
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments->add($appointment);
            $appointment->setUser($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): static
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getUser() === $this) {
                $appointment->setUser(null);
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
            $task->setUser($this);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getUser() === $this) {
                $task->setUser(null);
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
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setUser($this);
        }

        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getUser() === $this) {
                $note->setUser(null);
            }
        }

        return $this;
    }

}