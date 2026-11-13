<?php

namespace App\User\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Modelo de persistência da tabela `users`. A unicidade de `username` e `email` é garantida
 * pelas constraints da migration. A entidade de domínio é App\User\Domain\Entity\User.
 */
#[ORM\Entity]
#[ORM\Table(name: 'users')]
class UserEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    public ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    public string $username;

    #[ORM\Column(type: Types::STRING, length: 180)]
    public string $email;

    #[ORM\Column(name: 'password', type: Types::STRING, length: 255)]
    public string $passwordHash;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    public array $roles = [];

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN)]
    public bool $isActive = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;
}
