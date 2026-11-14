<?php

namespace App\User\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Modelo de persistência da tabela `roles` (o catálogo de capacidades). Só leitura pela aplicação: as roles são
 * criadas por migration, junto com o código que as checa.
 */
#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: 'roles')]
class RoleEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::SMALLINT, options: ['unsigned' => true])]
    public ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 60)]
    public string $code;

    #[ORM\Column(type: Types::STRING, length: 100)]
    public string $name;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $description = null;
}
