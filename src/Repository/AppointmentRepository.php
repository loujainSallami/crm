<?php

namespace App\Repository;

use App\Entity\Appointment;
use App\Entity\CrmLead;
use App\Entity\VicidialLead;
use App\Entity\CrmUser;
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

    // Trouver un rendez-vous par ID avec jointures
    public function findWithDetails(int $id): ?Appointment
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->leftJoin('a.lead', 'vl') // ✅ corrigé
            ->leftJoin('a.Note', 'n')
            ->leftJoin('a.tasks', 't')
            ->leftJoin('a.notifications', 'ntf')
            ->addSelect('u', 'vl', 'n', 't', 'ntf')
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // Trouver tous les rendez-vous d'un utilisateur avec pagination
    public function findByUser(CrmUser $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
    
    

    // Trouver les rendez-vous entre deux dates
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

    // Trouver les rendez-vous par lead
    public function findByLead(CrmLead $lead): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.lead = :lead') // ✅ corrigé
            ->setParameter('lead', $lead)
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Trouver les prochains rendez-vous (non passés)
    public function findUpcoming(int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.startTime >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('a.startTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // Trouver des rendez-vous avec critères multiples (recherche avancée)
    public function findByCriteria(array $criteria): array
    {
        $qb = $this->createQueryBuilder('a');

        if (isset($criteria['user'])) {
            $qb->andWhere('a.user = :user')
               ->setParameter('user', $criteria['user']);
        }

        if (isset($criteria['lead'])) {
            $qb->andWhere('a.lead = :lead') // ✅ corrigé
               ->setParameter('lead', $criteria['lead']);
        }

        if (isset($criteria['startDate'])) {
            $qb->andWhere('a.startTime >= :startDate')
               ->setParameter('startDate', $criteria['startDate']);
        }

        if (isset($criteria['endDate'])) {
            $qb->andWhere('a.startTime <= :endDate')
               ->setParameter('endDate', $criteria['endDate']);
        }

        if (isset($criteria['search'])) {
            $qb->andWhere('a.description LIKE :search')
               ->setParameter('search', '%'.$criteria['search'].'%');
        }

        return $qb->orderBy('a.startTime', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    // Compter le nombre de rendez-vous par utilisateur
    public function countByUser(CrmUser $user): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findUpcomingByUser(CrmUser $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.startTime >= :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllAppointments(): array
    {
        return $this->createQueryBuilder('a')
                    ->orderBy('a.startTime', 'ASC')
                    ->getQuery()
                    ->getResult();
    }
}
