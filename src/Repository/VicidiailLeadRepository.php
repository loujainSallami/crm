<?php

namespace App\Repository;

use App\Entity\Vicidial\CrmLead;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VicidiailLeadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CrmLead::class);
    }

    public function findByCampaignId(int $campaignId): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.campaign = :campaign')
            ->setParameter('campaign', $campaignId) // OK si campaign est une relation ManyToOne (Campaign)
            ->orderBy('l.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /*
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.status = :status')
            ->setParameter('status', $status)
            ->orderBy('l.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
    */
}