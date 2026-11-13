<?php

namespace App\Task\Infrastructure\Persistence\Doctrine\Entity;

use App\Task\Domain\Enum\TaskHistoryAction;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Modelo de persistência da tabela `task_history` (somente inserção). `field_name` não é usada e fica nula.
 */
#[ORM\Entity]
#[ORM\Table(name: 'task_history')]
class TaskHistoryEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TaskEntity::class)]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', nullable: false)]
    public TaskEntity $task;

    #[ORM\Column(name: 'actor_id', type: Types::INTEGER, options: ['unsigned' => true])]
    public int $actorId;

    #[ORM\Column(type: Types::STRING, length: 40, enumType: TaskHistoryAction::class)]
    public TaskHistoryAction $action;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'old_value', type: Types::JSON, nullable: true)]
    public ?array $oldValue = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'new_value', type: Types::JSON, nullable: true)]
    public ?array $newValue = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;
}
