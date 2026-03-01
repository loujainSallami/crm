<?php

namespace App\Entity\CRM;

use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\NoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
class Note
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isImportant = false;

    #[ORM\ManyToOne(inversedBy: 'notes')]
    #[ORM\JoinColumn(name: 'crm_user_id', referencedColumnName: 'user_id', nullable: false)]
    private ?CrmUser $crmUser = null;

    #[ORM\ManyToOne(inversedBy: 'notes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Appointment $appointment = null;

    public function getId(): ?int { return $this->id; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
    public function isImportant(): bool { return $this->isImportant; }
    public function setIsImportant(bool $isImportant): static { $this->isImportant = $isImportant; return $this; }
    public function getCrmUser(): ?CrmUser { return $this->crmUser; }
    public function setCrmUser(?CrmUser $crmUser): static { $this->crmUser = $crmUser; return $this; }
    public function getAppointment(): ?Appointment { return $this->appointment; }
    public function setAppointment(?Appointment $appointment): static { $this->appointment = $appointment; return $this; }
}