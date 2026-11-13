<?php

namespace App\User\Domain\Entity;

use App\User\Domain\Exception\InvalidUserException;

final class User
{
    private ?int $id = null;

    /**
     * @param list<string> $roles
     */
    private function __construct(
        private string $username,
        private string $email,
        private string $passwordHash,
        private array $roles,
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

        return new self($username, $email, $passwordHash, ['ROLE_USER'], true, $now, $now);
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

    /**
     * @return list<string>
     */
    public function roles(): array
    {
        return $this->roles;
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
