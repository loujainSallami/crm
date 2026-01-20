<?php

namespace App\Entity;

use App\Repository\VicidialCampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VicidialCampaignRepository::class)]
#[ORM\Table(name: "campaigns")]
class Campaign
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $campaign_name = null;

    #[ORM\Column(length: 1, nullable: true)]
    private ?string $active = null;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $dial_status_a = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $web_form_address = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $scheduled_callbacks = null;

    /**
     * @var Collection<int, CrmLead>
     */
    #[ORM\OneToMany(targetEntity: CrmLead::class, mappedBy: 'campaign')]
    private Collection $crmLeads;

    public function __construct()
    {
        $this->crmLeads = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getCampaignName(): ?string
    {
        return $this->campaign_name;
    }

    public function setCampaignName(?string $campaign_name): static
    {
        $this->campaign_name = $campaign_name;
        return $this;
    }

    public function getActive(): ?string
    {
        return $this->active;
    }

    public function setActive(?string $active): static
    {
        $this->active = $active;
        return $this;
    }

    public function getDialStatusA(): ?string
    {
        return $this->dial_status_a;
    }

    public function setDialStatusA(?string $dial_status_a): static
    {
        $this->dial_status_a = $dial_status_a;
        return $this;
    }

    public function getWebFormAddress(): ?string
    {
        return $this->web_form_address;
    }

    public function setWebFormAddress(?string $web_form_address): static
    {
        $this->web_form_address = $web_form_address;
        return $this;
    }

    public function getScheduledCallbacks(): ?string
    {
        return $this->scheduled_callbacks;
    }

    public function setScheduledCallbacks(?string $scheduled_callbacks): static
    {
        $this->scheduled_callbacks = $scheduled_callbacks;
        return $this;
    }

    /**
     * @return Collection<int, CrmLead>
     */
    public function getCrmLeads(): Collection
    {
        return $this->crmLeads;
    }

    public function addCrmLead(CrmLead $crmLead): static
    {
        if (!$this->crmLeads->contains($crmLead)) {
            $this->crmLeads->add($crmLead);
            $crmLead->setCampaign($this);
        }

        return $this;
    }

    public function removeCrmLead(CrmLead $crmLead): static
    {
        if ($this->crmLeads->removeElement($crmLead)) {
            // set the owning side to null (unless already changed)
            if ($crmLead->getCampaign() === $this) {
                $crmLead->setCampaign(null);
            }
        }

        return $this;
    }
}
