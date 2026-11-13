<?php

namespace App\User\Infrastructure\Security;

use App\User\Application\Port\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface as SymfonyHasher;

/**
 * Usa o hasher configurado em security.yaml (password_hashers) para o SecurityUser.
 */
final class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(private readonly PasswordHasherFactoryInterface $hasherFactory)
    {
    }

    public function hash(string $plainPassword): string
    {
        return $this->hasher()->hash($plainPassword);
    }

    public function verify(string $passwordHash, string $plainPassword): bool
    {
        return $this->hasher()->verify($passwordHash, $plainPassword);
    }

    private function hasher(): SymfonyHasher
    {
        return $this->hasherFactory->getPasswordHasher(SecurityUser::class);
    }
}
