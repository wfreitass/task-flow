<?php

namespace App\Task\Domain\Entity;

use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Exception\InvalidStatusTransitionException;
use App\Task\Domain\Exception\TaskAccessDeniedException;
use App\Task\Domain\Exception\InvalidTaskException;

final class Task
{
    private ?int $id = null;

    private function __construct(
        private int $createdBy,
        private ?int $assignedTo,
        private string $title,
        private ?string $description,
        private TaskPriority $priority,
        private TaskStatus $status,
        private ?\DateTimeImmutable $startDate,
        private ?\DateTimeImmutable $dueDate,
        private ?\DateTimeImmutable $completedAt,
        private ?\DateTimeImmutable $deletedAt,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        int $createdBy,
        string $title,
        ?string $description,
        TaskPriority $priority,
        ?\DateTimeImmutable $startDate,
        ?\DateTimeImmutable $dueDate,
        ?int $assignedTo,
        \DateTimeImmutable $now,
    ): self {
        if ($createdBy <= 0) {
            throw InvalidTaskException::creatorRequired();
        }

        $startDate = self::toUtc($startDate);
        $dueDate = self::toUtc($dueDate);
        self::assertDateRange($startDate, $dueDate);

        return new self(
            createdBy: $createdBy,
            assignedTo: $assignedTo,
            title: self::normalizeTitle($title),
            description: self::normalizeDescription($description),
            priority: $priority,
            status: TaskStatus::Backlog,
            startDate: $startDate,
            dueDate: $dueDate,
            completedAt: null,
            deletedAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function isCreatedBy(int $userId): bool
    {
        return $this->createdBy === $userId;
    }

    public function isAssignedTo(int $userId): bool
    {
        return null !== $this->assignedTo && $this->assignedTo === $userId;
    }

    /**
     * Quem criou e quem é o responsável enxergam a tarefa.
     */
    public function canBeViewedBy(int $userId): bool
    {
        return $this->isCreatedBy($userId) || $this->isAssignedTo($userId);
    }

    /**
     * Editar dados, atribuir e excluir são operações de quem criou a tarefa. O responsável pode
     * ver, mudar o status e comentar (essas não passam por aqui).
     *
     * @throws TaskAccessDeniedException
     */
    public function assertManagedBy(int $userId): void
    {
        if (!$this->isCreatedBy($userId)) {
            throw new TaskAccessDeniedException();
        }
    }

    /**
     * Atribui a tarefa a um usuário (ou a ninguém, com null) e devolve o que mudou, para o histórico.
     * Que o usuário exista e esteja ativo é conferido antes, pela aplicação: aqui só há ids.
     *
     * @return array{old: array<string, int|null>, new: array<string, int|null>}
     */
    public function assignTo(?int $userId, \DateTimeImmutable $now): array
    {
        if ($userId === $this->assignedTo) {
            return ['old' => [], 'new' => []];
        }

        $old = ['assigned_to' => $this->assignedTo];
        $this->assignedTo = $userId;
        $this->updatedAt = $now;

        return ['old' => $old, 'new' => ['assigned_to' => $userId]];
    }

    /**
     * Altera os dados da tarefa para os valores informados e devolve apenas o que mudou (para o histórico).
     * Nada muda, inclusive `updatedAt`, se os valores forem iguais aos atuais. O status tem método próprio.
     *
     * @return array{old: array<string, string|null>, new: array<string, string|null>}
     */
    public function changeDetails(
        string $title,
        ?string $description,
        TaskPriority $priority,
        ?\DateTimeImmutable $startDate,
        ?\DateTimeImmutable $dueDate,
        \DateTimeImmutable $now,
    ): array {
        // Valida tudo antes de alterar qualquer campo.
        $title = self::normalizeTitle($title);
        $description = self::normalizeDescription($description);
        $startDate = self::toUtc($startDate);
        $dueDate = self::toUtc($dueDate);
        self::assertDateRange($startDate, $dueDate);

        $old = [];
        $new = [];

        if ($title !== $this->title) {
            $old['title'] = $this->title;
            $new['title'] = $title;
            $this->title = $title;
        }
        if ($description !== $this->description) {
            $old['description'] = $this->description;
            $new['description'] = $description;
            $this->description = $description;
        }
        if ($priority !== $this->priority) {
            $old['priority'] = $this->priority->value;
            $new['priority'] = $priority->value;
            $this->priority = $priority;
        }
        if (self::formatDate($startDate) !== self::formatDate($this->startDate)) {
            $old['start_date'] = self::formatDate($this->startDate);
            $new['start_date'] = self::formatDate($startDate);
            $this->startDate = $startDate;
        }
        if (self::formatDate($dueDate) !== self::formatDate($this->dueDate)) {
            $old['due_date'] = self::formatDate($this->dueDate);
            $new['due_date'] = self::formatDate($dueDate);
            $this->dueDate = $dueDate;
        }

        if ([] !== $new) {
            $this->updatedAt = $now;
        }

        return ['old' => $old, 'new' => $new];
    }

    /**
     * Muda o status se a transição for permitida (mesmas regras de `task_status_transitions`).
     * Ao entrar em `done` registra a conclusão; ao sair, limpa (como o trigger do banco).
     *
     * @return bool false se a tarefa já estava no status pedido
     *
     * @throws InvalidStatusTransitionException
     */
    public function changeStatus(TaskStatus $target, \DateTimeImmutable $now): bool
    {
        if ($target === $this->status) {
            return false;
        }
        if (!$this->status->canTransitionTo($target)) {
            throw new InvalidStatusTransitionException($this->status, $target);
        }

        $this->status = $target;
        $this->completedAt = TaskStatus::Done === $target ? ($this->completedAt ?? $now) : null;
        $this->updatedAt = $now;

        return true;
    }

    /**
     * Exclusão lógica: a tarefa deixa de aparecer, mas o registro e o histórico permanecem.
     */
    public function delete(\DateTimeImmutable $now): void
    {
        $this->deletedAt ??= $now;
        $this->updatedAt = $now;
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function createdBy(): int
    {
        return $this->createdBy;
    }

    public function assignedTo(): ?int
    {
        return $this->assignedTo;
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

    public function startDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function dueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function completedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function deletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
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
     * @return array<string, string|int|null>
     */
    public function snapshot(): array
    {
        return [
            'assigned_to' => $this->assignedTo,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'start_date' => self::formatDate($this->startDate),
            'due_date' => self::formatDate($this->dueDate),
            'completed_at' => self::formatDate($this->completedAt),
        ];
    }

    private static function normalizeTitle(string $title): string
    {
        $title = trim($title);
        if ('' === $title) {
            throw InvalidTaskException::titleRequired();
        }
        if (mb_strlen($title) > InvalidTaskException::TITLE_MAX_LENGTH) {
            throw InvalidTaskException::titleTooLong();
        }

        return $title;
    }

    private static function normalizeDescription(?string $description): ?string
    {
        $description = null === $description ? null : trim($description);

        return '' === $description ? null : $description;
    }

    /**
     * Mesma regra do CHECK `chk_tasks_dates` da tabela: o vencimento não pode ser anterior ao início.
     */
    private static function assertDateRange(?\DateTimeImmutable $startDate, ?\DateTimeImmutable $dueDate): void
    {
        if (null !== $startDate && null !== $dueDate && $dueDate < $startDate) {
            throw InvalidTaskException::dueBeforeStart();
        }
    }

    private static function toUtc(?\DateTimeImmutable $date): ?\DateTimeImmutable
    {
        return $date?->setTimezone(new \DateTimeZone('UTC'));
    }

    private static function formatDate(?\DateTimeImmutable $date): ?string
    {
        return $date?->setTimezone(new \DateTimeZone('UTC'))->format(\DATE_ATOM);
    }
}
