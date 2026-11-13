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
        public readonly ?\DateTimeImmutable $startDate,
        public readonly ?\DateTimeImmutable $dueDate,
        public readonly ?\DateTimeImmutable $completedAt,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
        /** @var list<AttachmentOutput>|null null = anexos não carregados nesta operação */
        public readonly ?array $attachments = null,
        public readonly ?AssigneeOutput $assignee = null,
    ) {
    }

    /**
     * @param list<AttachmentOutput>|null $attachments
     */
    public static function fromTask(Task $task, ?array $attachments = null, ?AssigneeOutput $assignee = null): self
    {
        return new self(
            (int) $task->id(),
            $task->createdBy(),
            $task->title(),
            $task->description(),
            $task->priority(),
            $task->status(),
            $task->startDate(),
            $task->dueDate(),
            $task->completedAt(),
            $task->createdAt(),
            $task->updatedAt(),
            $attachments,
            $assignee,
        );
    }
}
