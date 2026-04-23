<?php

namespace App\Repository;

use App\Entity\CRM\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    /**
     * Trouver un rendez-vous par ID avec ses relations CRM
     */
    public function findWithDetails(int $id): ?Appointment
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.notes', 'n')
            ->leftJoin('a.tasks', 't')
            ->leftJoin('a.notifications', 'ntf')
            ->addSelect('n', 't', 'ntf')
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouver les rendez-vous entre deux dates
     */
    public function findBetweenDates(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.startTime BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les prochains rendez-vous
     */
    public function findUpcoming(int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.startTime >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.startTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avancée
     */
    public function findByCriteria(array $criteria): array
    {
        $qb = $this->createQueryBuilder('a');

        if (!empty($criteria['vicidialUser'])) {
            $qb->andWhere('a.vicidialUser = :vicidialUser')
               ->setParameter('vicidialUser', $criteria['vicidialUser']);
        }

        if (array_key_exists('vicidialLeadId', $criteria) && $criteria['vicidialLeadId'] !== null) {
            $qb->andWhere('a.vicidialLeadId = :vicidialLeadId')
               ->setParameter('vicidialLeadId', $criteria['vicidialLeadId']);
        }

        if (
            array_key_exists('vicidialCampaignId', $criteria) &&
            $criteria['vicidialCampaignId'] !== null &&
            $criteria['vicidialCampaignId'] !== ''
        ) {
            $qb->andWhere('a.vicidialCampaignId = :vicidialCampaignId')
               ->setParameter('vicidialCampaignId', $criteria['vicidialCampaignId']);
        }

        if (!empty($criteria['startDate'])) {
            $qb->andWhere('a.startTime >= :startDate')
               ->setParameter('startDate', $criteria['startDate']);
        }

        if (!empty($criteria['endDate'])) {
            $qb->andWhere('a.startTime <= :endDate')
               ->setParameter('endDate', $criteria['endDate']);
        }

        if (!empty($criteria['search'])) {
            $qb->andWhere('a.description LIKE :search')
               ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        return $qb->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter les rendez-vous par utilisateur Vicidial
     */
    public function countByVicidialUser(string $user): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.vicidialUser = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouver tous les rendez-vous d'un utilisateur Vicidial
     */
    public function findByVicidialUser(string $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.vicidialUser = :user')
            ->setParameter('user', $user)
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les rendez-vous à venir d'un utilisateur Vicidial
     */
    public function findUpcomingByVicidialUser(string $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.vicidialUser = :user')
            ->andWhere('a.startTime >= :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les rendez-vous par lead Vicidial
     */
    public function findByVicidialLeadId(int $leadId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.vicidialLeadId = :leadId')
            ->setParameter('leadId', $leadId)
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourner tous les rendez-vous
     */
    public function findAllAppointments(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie les conflits de planning pour un utilisateur Vicidial
     */
    public function findConflictingAppointmentsByUser(
        string $vicidialUser,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?Appointment $exclude = null
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.vicidialUser = :vicidialUser')
            ->andWhere('a.startTime < :end')
            ->andWhere('a.endTime > :start')
            ->setParameter('vicidialUser', $vicidialUser)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($exclude !== null && $exclude->getId() !== null) {
            $qb->andWhere('a.id != :excludeId')
               ->setParameter('excludeId', $exclude->getId());
        }

        return $qb->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}