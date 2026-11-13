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

    /**
     * Transições permitidas a partir deste status. É o espelho da tabela `task_status_transitions`
     * (migration Version20261111000000), que o trigger de UPDATE também aplica como rede de segurança.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Backlog => [self::Todo, self::Canceled],
            self::Todo => [self::Backlog, self::InProgress, self::Blocked, self::Canceled],
            self::InProgress => [self::Todo, self::Blocked, self::Done, self::Canceled],
            self::Blocked => [self::Todo, self::InProgress, self::Canceled],
            self::Done, self::Canceled => [self::Todo],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return \in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
