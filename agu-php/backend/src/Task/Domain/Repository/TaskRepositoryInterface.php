<?php

namespace App\Task\Domain\Repository;

use App\Task\Domain\Entity\Task;

interface TaskRepositoryInterface
{
    /**
     * Persiste uma nova tarefa. Após a chamada, a tarefa tem id.
     */
    public function add(Task $task): void;
}
