<?php

namespace App\Service;

use App\Entity\CrmLead;
use App\Entity\VicidailLead;
use App\Entity\VicidialLead;
use App\Repository\VicidiailLeadRepository;
use App\Repository\VicidialLeadRepository;
use Doctrine\ORM\EntityNotFoundException; // Importation correcte
use Doctrine\ORM\EntityManagerInterface;

class VicidialLeadService
{
    private VicidiailLeadRepository $vicidialLeadRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(VicidiailLeadRepository $vicidialLeadRepository, EntityManagerInterface $entityManager)
    {
        $this->vicidialLeadRepository = $vicidialLeadRepository;
        $this->entityManager = $entityManager;
    }

    public function createLead(CrmLead $lead): CrmLead
    {
        $this->entityManager->persist($lead);
        $this->entityManager->flush();

        return $lead;
    }

    public function updateLead(CrmLead $lead): CrmLead
    {
        $this->entityManager->flush(); // Juste un flush pour mettre à jour
        return $lead;
    }

    public function deleteLead(CrmLead $lead): void
    {
        $this->entityManager->remove($lead);
        $this->entityManager->flush();
    }

    public function getAllLeads(): array
    {
        return $this->vicidialLeadRepository->findAll();
    }

    public function getLeadsByCampaign($campaignId): array
    {
        return $this->vicidialLeadRepository->findByCampaign($campaignId);
    }
    public function getLeadById(int $id): CrmLead
    {
        $lead = $this->vicidialLeadRepository->find($id);
        if (!$lead) {
            throw new EntityNotFoundException("Lead not found for id: $id");
        }
        return $lead;
    }
    public function getLeadsByStatus($statusId): array
    {
        return $this->vicidialLeadRepository->findByStatus($statusId);
    }
}
