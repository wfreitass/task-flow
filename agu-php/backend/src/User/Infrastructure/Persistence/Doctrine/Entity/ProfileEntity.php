<?php

namespace App\User\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Modelo de persistência da tabela `profiles`. As roles do profile ficam na tabela de ligação `profile_roles`
 * (N:N). A entidade de domínio é App\User\Domain\Entity\Profile.
 */
#[ORM\Entity]
#[ORM\Table(name: 'profiles')]
class ProfileEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::SMALLINT, options: ['unsigned' => true])]
    public ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    public string $code;

    #[ORM\Column(type: Types::STRING, length: 100)]
    public string $name;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $description = null;

    #[ORM\Column(name: 'is_system', type: Types::BOOLEAN)]
    public bool $isSystem = false;

    /** @var Collection<int, RoleEntity> */
    #[ORM\ManyToMany(targetEntity: RoleEntity::class)]
    #[ORM\JoinTable(name: 'profile_roles')]
    #[ORM\JoinColumn(name: 'profile_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'role_id', referencedColumnName: 'id')]
    public Collection $roles;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }
}
