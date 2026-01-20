<?php

namespace App\Service;

use App\Entity\Campaign;
use App\Repository\VicidialCampaignRepository;
use Doctrine\ORM\EntityManagerInterface;

class VicidialCampaignService
{
    private VicidialCampaignRepository $campaignRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(VicidialCampaignRepository $campaignRepository, EntityManagerInterface $entityManager)
    {
        $this->campaignRepository = $campaignRepository;
        $this->entityManager = $entityManager;
    }

    public function createCampaign(string $name, string $isActive): Campaign
    {
        $campaign = new Campaign(); // instanciation correcte
        $campaign->setCampaignName($name);
        $campaign->setActive($isActive); // 'Y' ou 'N' par exemple

        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        return $campaign;
    }

    public function getAllCampaigns(): array
    {
        return $this->campaignRepository->findAll();
    }

    public function getCampaignById(int $id): ?Campaign
    {
        return $this->campaignRepository->find($id);
    }

    public function updateCampaign(Campaign $campaign): void
    {
        $this->entityManager->flush();
    }

    public function deleteCampaign(Campaign $campaign): void
    {
        $this->entityManager->remove($campaign);
        $this->entityManager->flush();
    }
}
