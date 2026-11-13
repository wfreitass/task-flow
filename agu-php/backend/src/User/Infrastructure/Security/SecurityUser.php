<?php

namespace App\User\Infrastructure\Security;

use App\User\Domain\Entity\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Adaptador entre a entidade de domínio User e o componente de segurança do Symfony.
 */
final class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(private readonly User $user)
    {
    }

    public function id(): int
    {
        return (int) $this->user->id();
    }

    public function getUserIdentifier(): string
    {
        return $this->user->username();
    }

    public function getRoles(): array
    {
        return $this->user->roles();
    }

    public function getPassword(): string
    {
        return $this->user->passwordHash();
    }

    public function eraseCredentials(): void
    {
    }
}
