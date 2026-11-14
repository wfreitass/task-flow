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
        return $this->user->email();
    }

    public function getRoles(): array
    {
        // ROLE_USER é o papel básico de todo usuário autenticado; o outro é o perfil (admin ou executor).
        return ['ROLE_USER', RoleNames::toSecurity($this->user->role())];
    }

    public function getPassword(): string
    {
        return $this->user->passwordHash();
    }

    public function eraseCredentials(): void
    {
    }
}
