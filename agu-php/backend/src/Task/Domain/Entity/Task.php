<?php

namespace App\Task\Domain\Entity;

use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Exception\InvalidTaskException;

final class Task
{
    private ?int $id = null;

    private function __construct(
        private int $createdBy,
        private string $title,
        private ?string $description,
        private TaskPriority $priority,
        private TaskStatus $status,
        private ?\DateTimeImmutable $dueDate,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        int $createdBy,
        string $title,
        ?string $description,
        TaskPriority $priority,
        ?\DateTimeImmutable $dueDate,
        \DateTimeImmutable $now,
    ): self {
        if ($createdBy <= 0) {
            throw InvalidTaskException::creatorRequired();
        }

        $title = trim($title);
        if ('' === $title) {
            throw InvalidTaskException::titleRequired();
        }
        if (mb_strlen($title) > InvalidTaskException::TITLE_MAX_LENGTH) {
            throw InvalidTaskException::titleTooLong();
        }

        $description = null === $description ? null : trim($description);
        if ('' === $description) {
            $description = null;
        }

        return new self(
            createdBy: $createdBy,
            title: $title,
            description: $description,
            priority: $priority,
            status: TaskStatus::Backlog,
            dueDate: $dueDate?->setTimezone(new \DateTimeZone('UTC')),
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function createdBy(): int
    {
        return $this->createdBy;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function priority(): TaskPriority
    {
        return $this->priority;
    }

    public function status(): TaskStatus
    {
        return $this->status;
    }

    public function dueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Estado relevante da tarefa, em tipos escalares, para o histórico.
     *
     * @return array<string, string|null>
     */
    public function snapshot(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'due_date' => $this->dueDate?->format(\DATE_ATOM),
        ];
    }
}
