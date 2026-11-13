<?php

namespace App\Task\Domain\Repository;

use App\Task\Domain\Entity\Task;

final class TaskPage
{
    /**
     * @param list<Task> $tasks tarefas da página pedida
     * @param int        $total total de tarefas que atendem aos filtros, em todas as páginas
     */
    public function __construct(
        public readonly array $tasks,
        public readonly int $total,
    ) {
    }
}
