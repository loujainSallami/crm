<?php

namespace App\Repository;


use App\Entity\CrmLead;
use App\Entity\VicidialLead;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VicidiailLeadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CrmLead::class);
    }


    public function findByCampaign($campaignId)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.campaign = :campaignId')
            ->setParameter('campaignId', $campaignId)
            ->orderBy('v.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus($statusId)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.status = :statusId')
            ->setParameter('statusId', $statusId)
            ->orderBy('v.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
