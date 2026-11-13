<?php

namespace App\Task\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;
use App\Task\Domain\Enum\TaskStatus;

final class InvalidStatusTransitionException extends ConflictException
{
    public function __construct(TaskStatus $from, TaskStatus $to)
    {
        parent::__construct(
            'INVALID_STATUS_TRANSITION',
            sprintf('Não é possível mudar a tarefa de "%s" para "%s".', $from->value, $to->value),
            [
                'from' => $from->value,
                'to' => $to->value,
                'allowed' => array_map(static fn (TaskStatus $status): string => $status->value, $from->allowedTransitions()),
            ],
        );
    }
}
