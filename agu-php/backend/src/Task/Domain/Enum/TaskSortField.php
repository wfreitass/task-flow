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

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
