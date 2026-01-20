<?php

namespace App\Repository;

use App\Entity\VicidialCampaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VicidialCampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VicidialCampaign::class);
    }

    /**
     * Récupère toutes les campagnes actives.
     *
     * @return VicidialCampaign[]
     */
    public function findActiveCampaigns(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère une campagne par son nom.
     *
     * @param string $name
     * @return VicidialCampaign|null
     */
    public function findOneByName(string $name): ?VicidialCampaign
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère toutes les campagnes triées par nom.
     *
     * @return VicidialCampaign[]
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
