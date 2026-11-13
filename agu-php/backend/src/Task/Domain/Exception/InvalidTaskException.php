<?php

namespace App\Task\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidTaskException extends DomainException
{
    public const TITLE_MAX_LENGTH = 255;

    public static function titleRequired(): self
    {
        return new self('TASK_TITLE_REQUIRED', 'O título da tarefa é obrigatório.', ['title' => 'required']);
    }

    public static function titleTooLong(): self
    {
        return new self(
            'TASK_TITLE_TOO_LONG',
            sprintf('O título da tarefa deve ter no máximo %d caracteres.', self::TITLE_MAX_LENGTH),
            ['title' => 'too_long'],
        );
    }

    public static function dueBeforeStart(): self
    {
        return new self(
            'TASK_DUE_BEFORE_START',
            'A data de vencimento não pode ser anterior à data de início.',
            ['dueAt' => 'before_start'],
        );
    }

    public static function creatorRequired(): self
    {
        return new self('TASK_CREATOR_REQUIRED', 'A tarefa precisa de um usuário criador.', ['createdBy' => 'required']);
    }
}
