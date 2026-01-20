<?php

namespace App\Repository;

use App\Entity\Note;
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

    /**
     * Récupérer toutes les notes d’un rendez-vous
     *
     * @param int $appointmentId
     * @return Note[]
     */
    public function findByAppointment(int $appointmentId): array
{
    return $this->createQueryBuilder('n')
        ->andWhere('n.appointment = :appointmentId')
        ->setParameter('appointmentId', $appointmentId)
        ->orderBy('n.createdAt', 'DESC')
        ->getQuery()
        ->getResult();
}


    /**
     * Récupérer une note par son ID
     *
     * @param int $id
     * @return Note|null
     */
    public function findOneById(int $id): ?Note
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Supprimer une note par son ID
     *
     * @param Note $note
     */
    public function remove(Note $note, bool $flush = true): void
    {
        $this->_em->remove($note);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Ajouter ou mettre à jour une note
     *
     * @param Note $note
     */
    public function save(Note $note, bool $flush = true): void
    {
        $this->_em->persist($note);
        if ($flush) {
            $this->_em->flush();
        }
    }
}
