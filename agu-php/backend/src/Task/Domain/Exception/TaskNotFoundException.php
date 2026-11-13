<?php

namespace App\Task\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

/**
 * Também é usada quando a tarefa existe mas pertence a outro usuário: não revela a existência de ids alheios.
 */
final class TaskNotFoundException extends NotFoundException
{
    public function __construct()
    {
        parent::__construct('TASK_NOT_FOUND', 'Tarefa não encontrada.');
    }
}
