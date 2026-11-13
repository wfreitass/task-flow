<?php

namespace App\User\Domain\Entity;

use App\User\Domain\Exception\InvalidUserException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
final class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    /**
     * @param list<string> $roles
     */
    private function __construct(
        #[ORM\Column(type: Types::STRING, length: 50)]
        private string $username,
        #[ORM\Column(type: Types::STRING, length: 180)]
        private string $email,
        #[ORM\Column(name: 'password', type: Types::STRING, length: 255)]
        private string $passwordHash,
        #[ORM\Column(type: Types::JSON)]
        private array $roles,
        #[ORM\Column(name: 'is_active', type: Types::BOOLEAN)]
        private bool $isActive,
        #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
        private \DateTimeImmutable $createdAt,
        #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
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
}
