<?php

namespace App\Repository;

use App\Entity\CRM\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Récupère toutes les notifications d'un utilisateur Vicidial
     */
    public function findByVicidialUser(string $vicidialUser): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.vicidialUser = :vicidialUser')
            ->setParameter('vicidialUser', $vicidialUser)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les notifications non lues d'un utilisateur Vicidial
     */
    public function findUnreadByVicidialUser(string $vicidialUser): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.vicidialUser = :vicidialUser')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('vicidialUser', $vicidialUser)
            ->setParameter('isRead', false)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}