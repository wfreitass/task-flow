<?php

namespace App\Task\Infrastructure\Persistence\Doctrine\Entity;

use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Modelo de persistência da tabela `tasks` (migration Version20261111000000). Não tem regra de negócio:
 * é só a forma como o Doctrine lê e grava. A entidade de domínio é App\Task\Domain\Entity\Task;
 * a conversão entre as duas é feita pelo TaskMapper. A coluna `parent_task_id` ainda não é usada (depende da
 * decisão sobre subtarefas) e fica com o valor padrão do banco.
 */
#[ORM\Entity]
#[ORM\Table(name: 'tasks')]
class TaskEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    public ?int $id = null;

    #[ORM\Column(name: 'created_by', type: Types::INTEGER, options: ['unsigned' => true])]
    public int $createdBy;

    // Responsável pela tarefa (FK para users). Pode ser outro usuário que não quem a criou.
    #[ORM\Column(name: 'assigned_to', type: Types::INTEGER, nullable: true, options: ['unsigned' => true])]
    public ?int $assignedTo = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $title;

    #[ORM\Column(type: Types::TEXT, length: 65535, nullable: true)]
    public ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 12, enumType: TaskPriority::class)]
    public TaskPriority $priority;

    // Tipo DBAL "task_status" (config/packages/doctrine.yaml): converte o enum no id do catálogo `task_statuses`.
    #[ORM\Column(name: 'status_id', type: 'task_status')]
    public TaskStatus $status;

    #[ORM\Column(name: 'start_date', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(name: 'due_date', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(name: 'completed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;
}
