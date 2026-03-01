<?php

namespace App\Entity\Vicidial;

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

    #[ORM\Column(name: "campaign_name", length: 40, nullable: true)]
    private ?string $campaignName = null;

    #[ORM\Column(length: 1, nullable: true)]
    private ?string $active = null;

    #[ORM\Column(name: "dial_status_a", length: 6, nullable: true)]
    private ?string $dialStatusA = null;

    #[ORM\Column(name: "web_form_address", type: Types::TEXT, nullable: true)]
    private ?string $webFormAddress = null;

    #[ORM\Column(name: "scheduled_callbacks", length: 2, nullable: true)]
    private ?string $scheduledCallbacks = null;

    /**
     * @var Collection<int, CrmLead>
     */
    #[ORM\OneToMany(targetEntity: CrmLead::class, mappedBy: 'campaign')]
    private Collection $crmLeads;

    public function __construct()
    {
        $this->crmLeads = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getCampaignName(): ?string { return $this->campaignName; }
    public function setCampaignName(?string $campaignName): static { $this->campaignName = $campaignName; return $this; }

    public function getActive(): ?string { return $this->active; }
    public function setActive(?string $active): static { $this->active = $active; return $this; }

    public function getDialStatusA(): ?string { return $this->dialStatusA; }
    public function setDialStatusA(?string $dialStatusA): static { $this->dialStatusA = $dialStatusA; return $this; }

    public function getWebFormAddress(): ?string { return $this->webFormAddress; }
    public function setWebFormAddress(?string $webFormAddress): static { $this->webFormAddress = $webFormAddress; return $this; }

    public function getScheduledCallbacks(): ?string { return $this->scheduledCallbacks; }
    public function setScheduledCallbacks(?string $scheduledCallbacks): static { $this->scheduledCallbacks = $scheduledCallbacks; return $this; }

    /**
     * @return Collection<int, CrmLead>
     */
    public function getCrmLeads(): Collection { return $this->crmLeads; }

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
            if ($crmLead->getCampaign() === $this) {
                $crmLead->setCampaign(null);
            }
        }
        return $this;
    }
}