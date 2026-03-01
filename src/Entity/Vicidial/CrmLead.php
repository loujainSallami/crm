<?php

namespace App\Entity\Vicidial;

use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\VicidiailLeadRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VicidiailLeadRepository::class)]
#[ORM\Table(name: "lead")]
class CrmLead
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column]
    #[Groups(['lead:read'])]
    private ?int $id = null;

    #[ORM\Column(name: "first_name", length: 30)]
    #[Groups(['lead:read'])]
    private ?string $firstName = null;

    #[ORM\Column(name: "last_name", length: 30)]
    #[Groups(['lead:read'])]
    private ?string $lastName = null;

    #[ORM\Column(name: "phone_number", length: 18)]
    #[Groups(['lead:read'])]
    private ?string $phoneNumber = null;

    #[ORM\Column(name: "email", length: 255)]
    #[Groups(['lead:read'])]
    private ?string $email = null;

    // ✅ Relation Vicidial -> Vicidial OK (même EM)
    #[ORM\ManyToOne(targetEntity: Campaign::class, inversedBy: 'crmLeads')]
    #[ORM\JoinColumn(name: "campaign_id", referencedColumnName: "id", nullable: true)]
    #[Groups(['lead:read'])]
    private ?Campaign $campaign = null;

    public function getId(): ?int { return $this->id; }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(?string $firstName): static { $this->firstName = $firstName; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(?string $lastName): static { $this->lastName = $lastName; return $this; }

    public function getPhoneNumber(): ?string { return $this->phoneNumber; }
    public function setPhoneNumber(?string $phoneNumber): static { $this->phoneNumber = $phoneNumber; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getCampaign(): ?Campaign { return $this->campaign; }
    public function setCampaign(?Campaign $campaign): static { $this->campaign = $campaign; return $this; }
}