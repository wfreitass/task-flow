<?php

namespace App\Task\Domain\Enum;

/**
 * Códigos do catálogo `task_statuses` (migration Version20261111000000).
 * Os ids numéricos do catálogo ficam restritos à infraestrutura.
 */
enum TaskStatus: string
{
    case Backlog = 'backlog';
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Done = 'done';
    case Canceled = 'canceled';

    public function isTerminal(): bool
    {
        return self::Done === $this || self::Canceled === $this;
    }
}
