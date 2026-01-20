<?php

namespace App\Repository;

use App\Entity\CrmUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @extends ServiceEntityRepository<CrmUser>
 */
class VicidialUserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CrmUser::class); // ✅ CrmUser correct
    }

    public function save(CrmUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function remove(CrmUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof CrmUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPass($newHashedPassword);
        $this->save($user, true);
    }

    public function findByUserId(int $userId): ?CrmUser
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.id = :id') // ou u.user_id selon ton champ
            ->setParameter('id', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
