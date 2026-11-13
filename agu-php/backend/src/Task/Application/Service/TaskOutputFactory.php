<?php

namespace App\Task\Application\Service;

use App\Task\Application\DTO\AssigneeOutput;
use App\Task\Application\DTO\AttachmentOutput;
use App\Task\Application\DTO\TaskOutput;
use App\Task\Application\Port\UserDirectoryInterface;
use App\Task\Domain\Entity\Task;

/**
 * Monta a saída de tarefas incluindo o nome do responsável. A lista resolve todos os nomes de uma vez,
 * com uma só consulta, e nenhuma quando nenhuma tarefa tem responsável.
 */
final class TaskOutputFactory
{
    public function __construct(private readonly UserDirectoryInterface $directory)
    {
    }

    /**
     * @param list<AttachmentOutput>|null $attachments
     */
    public function one(Task $task, ?array $attachments = null): TaskOutput
    {
        $assignee = null === $task->assignedTo() ? null : ($this->directory->findByIds([$task->assignedTo()])[$task->assignedTo()] ?? null);

        return TaskOutput::fromTask($task, $attachments, null === $assignee ? null : new AssigneeOutput($assignee->id, $assignee->username));
    }

    /**
     * @param list<Task> $tasks
     *
     * @return list<TaskOutput>
     */
    public function many(array $tasks): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn (Task $task): ?int => $task->assignedTo(), $tasks),
            static fn (?int $id): bool => null !== $id,
        )));
        $users = [] === $ids ? [] : $this->directory->findByIds($ids);

        return array_map(static function (Task $task) use ($users): TaskOutput {
            $user = null === $task->assignedTo() ? null : ($users[$task->assignedTo()] ?? null);

            return TaskOutput::fromTask($task, null, null === $user ? null : new AssigneeOutput($user->id, $user->username));
        }, $tasks);
    }
}
