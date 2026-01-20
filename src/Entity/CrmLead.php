<?php

namespace App\Entity;
use Symfony\Component\Serializer\Annotation\Groups;

use App\Repository\VicidiailLeadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VicidiailLeadRepository::class)]
#[ORM\Table(name: "lead")]
class CrmLead
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column]
    #[Groups(groups: ['lead:read'])]

    private ?int $id = null;
    #[Groups(groups: ['lead:read'])]

    #[ORM\Column(name: "first_name", length: 30)]
    private ?string $firstName = null; // 
    #[ORM\Column(name: "last_name", length: 30)]
    #[Groups(groups: ['lead:read'])]

    private ?string $lastName = null; 

    #[ORM\Column(name: "phone_number", length: 18)]
    private ?string $phoneNumber = null; 

    #[ORM\Column(name: "email", length: 255)]
    private ?string $email = null;

    /**
     * @var Collection<int, Appointment>
     */
    #[ORM\OneToMany(targetEntity: Appointment::class, mappedBy: 'lead')]
    private Collection $appointments;
    
    #[ORM\ManyToOne(targetEntity: Campaign::class, inversedBy: 'crmlead')]
    #[ORM\JoinColumn(name: "campaign_id", referencedColumnName: "id", nullable: true)]    
    private ?Campaign $campaign = null;

   
    public function __construct()
    {
        $this->appointments = new ArrayCollection(); 
    }

    // Getters et setters...

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName; // Utilisation de camelCase
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName; // Utilisation de camelCase
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhoneNumber(): ?string // Changer le type de retour en string
    {
        return $this->phoneNumber; // Utilisation de camelCase
    }

    public function setPhoneNumber(string $phoneNumber): static // Changer le type d'argument en string
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getAppointments(): Collection // Renommé pour la cohérence
    {
        return $this->appointments;
    }

    public function getCampaign(): ?Campaign // Renommé pour la clarté
    {
        return $this->campaign;
    }

    public function setCampaign(?Campaign $campaign): static // Renommé pour la clarté
    {
        $this->campaign = $campaign;

        return $this;
    }
}








