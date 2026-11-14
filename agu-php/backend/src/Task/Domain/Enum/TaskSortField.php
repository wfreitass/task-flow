<?php

namespace App\Task\Domain\Enum;

/**
 * Campos pelos quais a listagem de tarefas pode ser ordenada.
 */
enum TaskSortField: string
{
    case CreatedAt = 'createdAt';
    case DueDate = 'dueDate';
    case Title = 'title';
    case Status = 'status';
    /** Só tem efeito numa busca por texto (q); nas demais listagens, vale a data de criação. */
    case Relevance = 'relevance';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
