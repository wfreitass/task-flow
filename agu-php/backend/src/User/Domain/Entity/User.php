<?php

namespace App\User\Domain\Entity;

use App\Shared\Domain\Permission;
use App\User\Domain\Enum\UserRole;
use App\User\Domain\Exception\InvalidUserException;

final class User
{
    private ?int $id = null;

    private function __construct(
        private string $username,
        private string $email,
        private string $passwordHash,
        private UserRole $role,
        private bool $isActive,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function register(string $username, string $email, string $passwordHash, \DateTimeImmutable $now): self
    {
        $username = trim($username);
        $length = mb_strlen($username);
        if ($length < 3 || $length > 50) {
            throw InvalidUserException::invalidUsername();
        }

        $email = mb_strtolower(trim($email));
        if (false === filter_var($email, \FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 180) {
            throw InvalidUserException::invalidEmail();
        }

        // Todo cadastro novo é executor; quem vira admin é promovido (ChangeUserRoleService ou app:user:set-role).
        return new self($username, $email, $passwordHash, UserRole::Executor, true, $now, $now);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function role(): UserRole
    {
        return $this->role;
    }

    public function isAdmin(): bool
    {
        return UserRole::Admin === $this->role;
    }

    public function can(Permission $permission): bool
    {
        return $this->role->can($permission);
    }

    /**
     * Muda o perfil. Que o último admin não seja rebaixado é regra de quem conhece os outros usuários
     * (ChangeUserRoleService), não deste objeto.
     *
     * @return bool false se o usuário já tinha este perfil
     */
    public function changeRole(UserRole $role, \DateTimeImmutable $now): bool
    {
        if ($role === $this->role) {
            return false;
        }

        $this->role = $role;
        $this->updatedAt = $now;

        return true;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
