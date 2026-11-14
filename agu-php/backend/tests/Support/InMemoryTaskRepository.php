<?php

namespace App\Tests\Support;

use App\Task\Domain\Entity\Task;
use App\Task\Domain\Repository\TaskCriteria;
use App\Task\Domain\Repository\TaskPage;
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

    public function save(Task $task): void
    {
        // As entidades são guardadas por referência: já refletem as alterações.
    }

    public function findAccessibleById(int $id, int $userId): ?Task
    {
        foreach ($this->tasks as $task) {
            if ($task->id() === $id && $task->canBeViewedBy($userId) && !$task->isDeleted()) {
                return $task;
            }
        }

        return null;
    }

    public function search(TaskCriteria $criteria): TaskPage
    {
        $owned = array_values(array_filter(
            $this->tasks,
            static fn (Task $task): bool => $task->canBeViewedBy($criteria->viewerId) && !$task->isDeleted(),
        ));

        return new TaskPage(\array_slice($owned, $criteria->offset(), $criteria->perPage), \count($owned));
    }

    public function findById(int $id): ?Task
    {
        foreach ($this->tasks as $task) {
            if ($task->id() === $id) {
                return $task;
            }
        }

        return null;
    }

    public function findAccessibleByIds(array $ids, int $userId): array
    {
        $found = [];
        foreach ($ids as $id) {
            $task = $this->findAccessibleById($id, $userId);
            if (null !== $task) {
                $found[] = $task;
            }
        }

        return $found;
    }

    public function findActiveBatchAfterId(int $afterId, int $limit): array
    {
        $batch = array_filter($this->tasks, static fn (Task $task): bool => (int) $task->id() > $afterId && !$task->isDeleted());

        return \array_slice(array_values($batch), 0, $limit);
    }
}
