<?php

namespace App\Security;

use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class VicidialUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private Connection $vicidialConnection
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $sql = "
            SELECT user, pass, full_name, user_level
            FROM vicidial_users
            WHERE user = :user
            LIMIT 1
        ";

        $row = $this->vicidialConnection->fetchAssociative($sql, [
            'user' => $identifier,
        ]);

        if (!$row) {
            $exception = new UserNotFoundException(sprintf('Utilisateur Vicidial "%s" introuvable.', $identifier));
            $exception->setUserIdentifier($identifier);
            throw $exception;
        }

        return new VicidialUser(
            $row['user'],
            $row['pass'],
            $row['full_name'] ?? null,
            isset($row['user_level']) ? (int) $row['user_level'] : null
        );
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof VicidialUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === VicidialUser::class;
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        // Non utilisé ici
    }
}