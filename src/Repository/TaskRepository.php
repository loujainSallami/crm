<?php

namespace App\Repository;

use App\Entity\CRM\Appointment;
use App\Entity\CRM\Task;
use App\Enum\TaskStatus;
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

    public function save(Task $task, bool $flush = true): void
    {
        $this->_em->persist($task);

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(Task $task, bool $flush = true): void
    {
        $this->_em->remove($task);

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findByVicidialUser(string $vicidialUser, int $page = 1, int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.vicidialUser = :vicidialUser')
            ->setParameter('vicidialUser', $vicidialUser)
            ->orderBy('t.dueDate', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByAppointment(Appointment $appointment): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.appointment = :appointment')
            ->setParameter('appointment', $appointment)
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingTasks(string $vicidialUser, int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.vicidialUser = :vicidialUser')
            ->andWhere('(t.dueDate >= :now OR t.dueDate IS NULL)')
            ->andWhere('t.completed = false')
            ->setParameter('vicidialUser', $vicidialUser)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('t.dueDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countOverdueTasks(string $vicidialUser): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.vicidialUser = :vicidialUser')
            ->andWhere('t.completed = false')
            ->andWhere('t.dueDate < :now')
            ->setParameter('vicidialUser', $vicidialUser)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByStatus(string $vicidialUser, TaskStatus $status): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.vicidialUser = :vicidialUser')
            ->andWhere('t.status = :status')
            ->setParameter('vicidialUser', $vicidialUser)
            ->setParameter('status', $status)
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}