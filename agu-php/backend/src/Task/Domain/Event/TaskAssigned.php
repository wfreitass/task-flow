<?php

namespace App\Task\Domain\Event;

/**
 * Uma tarefa foi atribuída a um usuário. É uma "foto" do momento, em dados simples (vai para uma fila e volta
 * depois): quem consome deve conferir se a atribuição continua valendo.
 */
final class TaskAssigned
{
    public function __construct(
        public readonly int $taskId,
        public readonly string $taskTitle,
        public readonly int $assigneeId,
        public readonly int $assignedById,
        public readonly string $occurredAt,
    ) {
    }
}
