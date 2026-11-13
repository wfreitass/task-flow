<?php

namespace App\Task\Domain\Entity;

use App\Task\Domain\Enum\TaskHistoryAction;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'task_history')]
final class TaskHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    /**
     * @param array<string, mixed>|null $oldValue
     * @param array<string, mixed>|null $newValue
     */
    private function __construct(
        #[ORM\ManyToOne(targetEntity: Task::class)]
        #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', nullable: false)]
        private Task $task,
        #[ORM\Column(name: 'actor_id', type: Types::INTEGER, options: ['unsigned' => true])]
        private int $actorId,
        #[ORM\Column(type: Types::STRING, length: 40, enumType: TaskHistoryAction::class)]
        private TaskHistoryAction $action,
        #[ORM\Column(name: 'old_value', type: Types::JSON, nullable: true)]
        private ?array $oldValue,
        #[ORM\Column(name: 'new_value', type: Types::JSON, nullable: true)]
        private ?array $newValue,
        #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
        private \DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * @param array<string, mixed>|null $oldValue
     * @param array<string, mixed>|null $newValue
     */
    public static function record(
        Task $task,
        int $actorId,
        TaskHistoryAction $action,
        ?array $oldValue,
        ?array $newValue,
        \DateTimeImmutable $at,
    ): self {
        return new self($task, $actorId, $action, $oldValue, $newValue, $at);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function task(): Task
    {
        return $this->task;
    }

    public function actorId(): int
    {
        return $this->actorId;
    }

    public function action(): TaskHistoryAction
    {
        return $this->action;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function newValue(): ?array
    {
        return $this->newValue;
    }
}
