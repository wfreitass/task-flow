<?php

namespace App\Task\Application\Service;

use App\Task\Application\DTO\TaskOutput;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;

final class GetTaskService
{
    public function __construct(private readonly TaskRepositoryInterface $tasks)
    {
    }

    public function execute(int $taskId, int $authenticatedUserId): TaskOutput
    {
        $task = $this->tasks->findOwnedById($taskId, $authenticatedUserId) ?? throw new TaskNotFoundException();

        return TaskOutput::fromTask($task);
    }
}
