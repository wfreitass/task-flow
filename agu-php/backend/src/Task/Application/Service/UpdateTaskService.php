<?php

namespace App\Task\Application\Service;

use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\TransactionRunnerInterface;
use App\Task\Application\DTO\TaskOutput;
use App\Task\Application\DTO\UpdateTaskInput;
use App\Task\Domain\Entity\TaskHistory;
use App\Task\Domain\Enum\TaskHistoryAction;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskHistoryRepositoryInterface;
use App\Task\Domain\Repository\TaskRepositoryInterface;

final class UpdateTaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskHistoryRepositoryInterface $history,
        private readonly ClockInterface $clock,
        private readonly TransactionRunnerInterface $transactions,
    ) {
    }

    /**
     * Altera os dados da tarefa (não o status). Só grava, e só registra histórico, se algo realmente mudou.
     */
    public function execute(UpdateTaskInput $input, int $authenticatedUserId): TaskOutput
    {
        return $this->transactions->run(function () use ($input, $authenticatedUserId): TaskOutput {
            $task = $this->tasks->findOwnedById($input->taskId, $authenticatedUserId) ?? throw new TaskNotFoundException();
            $now = $this->clock->now();

            $changes = $task->changeDetails(
                $input->has('title') ? (string) $input->title : $task->title(),
                $input->has('description') ? $input->description : $task->description(),
                $input->has('priority') && null !== $input->priority ? $input->priority : $task->priority(),
                $input->has('startDate') ? $input->startDate : $task->startDate(),
                $input->has('dueDate') ? $input->dueDate : $task->dueDate(),
                $now,
            );

            if ([] !== $changes['new']) {
                $this->tasks->save($task);
                $this->history->add(TaskHistory::record(
                    $task,
                    $authenticatedUserId,
                    TaskHistoryAction::Updated,
                    $changes['old'],
                    $changes['new'],
                    $now,
                ));
            }

            return TaskOutput::fromTask($task);
        });
    }
}
