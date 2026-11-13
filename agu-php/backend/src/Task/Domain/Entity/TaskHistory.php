<?php

namespace App\Task\Domain\Entity;

use App\Task\Domain\Enum\TaskHistoryAction;

final class TaskHistory
{
    private ?int $id = null;

    /**
     * @param array<string, mixed>|null $oldValue
     * @param array<string, mixed>|null $newValue
     */
    private function __construct(
        private Task $task,
        private int $actorId,
        private TaskHistoryAction $action,
        private ?array $oldValue,
        private ?array $newValue,
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
    public function oldValue(): ?array
    {
        return $this->oldValue;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function newValue(): ?array
    {
        return $this->newValue;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
