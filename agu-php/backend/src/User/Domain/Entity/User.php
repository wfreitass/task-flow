<?php

namespace App\User\Domain\Entity;

use App\User\Domain\Exception\InvalidUserException;

final class User
{
    private ?int $id = null;

    private function __construct(
        private string $username,
        private string $email,
        private string $passwordHash,
        private int $profileId,
        private bool $isActive,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function register(string $username, string $email, string $passwordHash, int $profileId, \DateTimeImmutable $now): self
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

        return new self($username, $email, $passwordHash, $profileId, true, $now, $now);
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
     * O id do profile do usuário. As roles dele vêm do profile (ver Profile e RoleCheckerInterface).
     */
    public function profileId(): int
    {
        return $this->profileId;
    }

    /**
     * Vincula o usuário a outro profile. Que o sistema não fique sem quem gerencie usuários é regra de quem
     * conhece os outros usuários (ChangeUserProfileService), não deste objeto.
     *
     * @return bool false se o usuário já tinha este profile
     */
    public function changeProfile(int $profileId, \DateTimeImmutable $now): bool
    {
        if ($profileId === $this->profileId) {
            return false;
        }

        $this->profileId = $profileId;
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
