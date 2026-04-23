<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class VicidialUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private string $username,
        private string $password,
        private ?string $fullName = null,
        private ?int $userLevel = null
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRoles(): array
    {

        if ($this->userLevel !== null && $this->userLevel >= 8) {
            $roles[] = 'ROLE_ADMIN';
        } else {
            $roles[] = 'ROLE_AGENT';
        }

        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function getUserLevel(): ?int
    {
        return $this->userLevel;
    }
}