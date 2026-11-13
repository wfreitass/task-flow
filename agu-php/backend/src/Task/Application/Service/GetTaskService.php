<?php

namespace App\Task\Application\Service;

use App\Task\Application\DTO\TaskOutput;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;

final class GetTaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskOutputFactory $outputs,
    ) {
    }

    public function execute(int $taskId, int $authenticatedUserId): TaskOutput
    {
        // Quem criou e quem é o responsável enxergam a tarefa.
        $task = $this->tasks->findAccessibleById($taskId, $authenticatedUserId) ?? throw new TaskNotFoundException();

        return $this->outputs->one($task);
    }
}
