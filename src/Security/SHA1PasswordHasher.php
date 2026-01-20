<?php
namespace App\Security;

use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class SHA1PasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plainPassword): string
    {
        return sha1($plainPassword);
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        return $hashedPassword === sha1($plainPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return false;
    }
}
