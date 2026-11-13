<?php

namespace App\Task\Application\DTO;

use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;

final class TaskOutput
{
    public function __construct(
        public readonly int $id,
        public readonly int $createdBy,
        public readonly string $title,
        public readonly ?string $description,
        public readonly TaskPriority $priority,
        public readonly TaskStatus $status,
        public readonly ?\DateTimeImmutable $dueDate,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromTask(Task $task): self
    {
        return new self(
            (int) $task->id(),
            $task->createdBy(),
            $task->title(),
            $task->description(),
            $task->priority(),
            $task->status(),
            $task->dueDate(),
            $task->createdAt(),
        );
    }
}
