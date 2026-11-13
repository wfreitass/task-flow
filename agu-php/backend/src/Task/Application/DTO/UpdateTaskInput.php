<?php

namespace App\Task\Application\DTO;

use App\Task\Domain\Enum\TaskPriority;

/**
 * Alteração parcial: só os campos listados em `$provided` são alterados. É o que distingue
 * "não enviado" (mantém) de "enviado como null" (limpa descrição, início, vencimento ou responsável).
 */
final class UpdateTaskInput
{
    public const FIELDS = ['title', 'description', 'priority', 'startDate', 'dueDate', 'assignedTo'];

    /**
     * @param list<string> $provided nomes dos campos enviados (de self::FIELDS)
     */
    public function __construct(
        public readonly int $taskId,
        public readonly array $provided,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?TaskPriority $priority = null,
        public readonly ?\DateTimeImmutable $startDate = null,
        public readonly ?\DateTimeImmutable $dueDate = null,
        public readonly ?int $assignedTo = null,
    ) {
    }

    public function has(string $field): bool
    {
        return \in_array($field, $this->provided, true);
    }
}
