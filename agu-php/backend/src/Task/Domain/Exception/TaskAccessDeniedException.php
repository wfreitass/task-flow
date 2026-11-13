<?php

namespace App\Task\Domain\Exception;

use App\Shared\Domain\Exception\ForbiddenException;

/**
 * O usuário enxerga a tarefa (é o responsável por ela), mas a operação é só de quem a criou.
 */
final class TaskAccessDeniedException extends ForbiddenException
{
    public function __construct()
    {
        parent::__construct('TASK_FORBIDDEN', 'Apenas quem criou a tarefa pode fazer esta operação.');
    }
}
