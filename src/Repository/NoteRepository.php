<?php

namespace App\Repository;

use App\Entity\CRM\Appointment;
use App\Entity\CRM\Note;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Note>
 *
 * @method Note|null find($id, $lockMode = null, $lockVersion = null)
 * @method Note|null findOneBy(array $criteria, array $orderBy = null)
 * @method Note[]    findAll()
 * @method Note[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    public function findByAppointment(Appointment $appointment, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.appointment = :appointment')
            ->setParameter('appointment', $appointment)
            ->orderBy('n.createdAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneById(int $id): ?Note
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByVicidialUser(string $vicidialUser, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.vicidialUser = :vicidialUser')
            ->setParameter('vicidialUser', $vicidialUser)
            ->orderBy('n.createdAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function searchByContent(string $query, int $limit = 10): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.content LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUserNotes(?string $vicidialUser = null): int
    {
        $qb = $this->createQueryBuilder('n')
            ->select('COUNT(n.id)');

        if ($vicidialUser !== null) {
            $qb->andWhere('n.vicidialUser = :vicidialUser')
               ->setParameter('vicidialUser', $vicidialUser);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countImportantNotes(?string $vicidialUser = null): int
    {
        $qb = $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.isImportant = :important')
            ->setParameter('important', true);

        if ($vicidialUser !== null) {
            $qb->andWhere('n.vicidialUser = :vicidialUser')
               ->setParameter('vicidialUser', $vicidialUser);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countTodayNotes(?string $vicidialUser = null): int
    {
        $start = new \DateTimeImmutable('today 00:00:00');
        $end = new \DateTimeImmutable('tomorrow 00:00:00');

        $qb = $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.createdAt >= :start')
            ->andWhere('n.createdAt < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($vicidialUser !== null) {
            $qb->andWhere('n.vicidialUser = :vicidialUser')
               ->setParameter('vicidialUser', $vicidialUser);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function remove(Note $note, bool $flush = true): void
    {
        $this->_em->remove($note);

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function save(Note $note, bool $flush = true): void
    {
        $this->_em->persist($note);

        if ($flush) {
            $this->_em->flush();
        }
    }
}