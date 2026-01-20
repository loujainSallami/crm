<?php

namespace App\Repository;

use App\Entity\CrmUser;
use App\Entity\Task;
use App\Entity\VicidialUser;
use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 *
 * @method Task|null find($id, $lockMode = null, $lockVersion = null)
 * @method Task|null findOneBy(array $criteria, array $orderBy = null)
 * @method Task[]    findAll()
 * @method Task[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * Sauvegarde une tâche
     */
    public function save(Task $task, bool $flush = true): void
    {
        $this->_em->persist($task);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Supprime une tâche
     */
    public function remove(Task $task, bool $flush = true): void
    {
        $this->_em->remove($task);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * 🔹 Récupère les tâches d'un utilisateur (avec pagination)
     */
    public function findByUser(CrmUser $user, int $page = 1, int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.dueDate', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 🔹 Récupère les tâches liées à un rendez-vous spécifique
     */
    public function findByAppointment(Appointment $appointment): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.appointment = :appointment')
            ->setParameter('appointment', $appointment)
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 🔹 Récupère les tâches à venir (non accomplies)
     */
    public function findUpcomingTasks(CrmUser $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('(t.dueDate >= :now OR t.dueDate IS NULL)')
            ->andWhere('t.completed = 0')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->orderBy('t.dueDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 🔹 Compte les tâches en retard (non terminées)
     */
    public function countOverdueTasks(CrmUser $user): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.user = :user')
            ->andWhere('t.completed = 0')
            ->andWhere('t.dueDate < :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * 🔹 Filtrer par statut (optionnel)
     */
    public function findByStatus(CrmUser $user, string $status): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('t.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
