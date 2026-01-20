<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\NoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
class Note
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['note:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['note:read'])]
    private ?string $content = null;

    #[ORM\Column]
    #[Groups(['note:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['note:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['note:read'])]
    private bool $isImportant = false;

    #[ORM\ManyToOne(inversedBy: 'notes')]
    #[ORM\JoinColumn(
        name: 'user_id',
        referencedColumnName: 'user_id',
        nullable: false
    )]
    private ?CrmUser $user = null;
    
    #[ORM\ManyToOne(inversedBy: 'notes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Appointment $appointment = null;


    // ================= GETTERS / SETTERS =================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
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

    public function isImportant(): bool
    {
        return $this->isImportant;
    }

    public function setIsImportant(bool $isImportant): static
    {
        $this->isImportant = $isImportant;
        return $this;
    }
}
