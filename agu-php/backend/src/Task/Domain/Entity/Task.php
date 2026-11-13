<?php

namespace App\Task\Domain\Entity;

use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Exception\InvalidTaskException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tasks')]
final class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    private function __construct(
        #[ORM\Column(name: 'created_by', type: Types::INTEGER, options: ['unsigned' => true])]
        private int $createdBy,
        #[ORM\Column(type: Types::STRING, length: 255)]
        private string $title,
        #[ORM\Column(type: Types::TEXT, length: 65535, nullable: true)]
        private ?string $description,
        #[ORM\Column(type: Types::STRING, length: 12, enumType: TaskPriority::class)]
        private TaskPriority $priority,
        // Tipo DBAL "task_status" (registrado em config/packages/doctrine.yaml): converte o enum no id do catálogo.
        #[ORM\Column(name: 'status_id', type: 'task_status')]
        private TaskStatus $status,
        #[ORM\Column(name: 'due_date', type: Types::DATETIME_IMMUTABLE, nullable: true)]
        private ?\DateTimeImmutable $dueDate,
        #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
        private \DateTimeImmutable $createdAt,
        #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
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
