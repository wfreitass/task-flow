<?php

namespace App\Task\Domain\Enum;

/**
 * Quais tarefas a listagem considera, do ponto de vista de quem consulta.
 */
enum TaskScope: string
{
    /** As que o usuário criou e as atribuídas a ele. */
    case All = 'all';
    /** Só as que o usuário criou. */
    case Created = 'created';
    /** Só as atribuídas ao usuário (ele é o responsável). */
    case Assigned = 'assigned';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
