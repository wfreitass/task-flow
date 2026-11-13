<?php

namespace App\Tests\Support;

use App\Task\Domain\Entity\Task;
use App\Task\Domain\Repository\TaskRepositoryInterface;

final class InMemoryTaskRepository implements TaskRepositoryInterface
{
    /** @var list<Task> */
    public array $tasks = [];

    public function add(Task $task): void
    {
        $this->tasks[] = $task;
        // Simula o id gerado pelo banco.
        (new \ReflectionProperty(Task::class, 'id'))->setValue($task, \count($this->tasks));
    }
}
