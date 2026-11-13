<?php

namespace App\Task\Application\Service;

use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\TransactionRunnerInterface;
use App\Task\Domain\Entity\TaskHistory;
use App\Task\Domain\Enum\TaskHistoryAction;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskHistoryRepositoryInterface;
use App\Task\Domain\Repository\TaskRepositoryInterface;

final class DeleteTaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskHistoryRepositoryInterface $history,
        private readonly ClockInterface $clock,
        private readonly TransactionRunnerInterface $transactions,
    ) {
    }

    /**
     * Exclusão lógica: a tarefa some das consultas, mas o registro e o histórico permanecem. Só quem criou a tarefa
     * pode excluí-la; o responsável não.
     */
    public function execute(int $taskId, int $authenticatedUserId): void
    {
        $this->transactions->run(function () use ($taskId, $authenticatedUserId): void {
            $task = $this->tasks->findAccessibleById($taskId, $authenticatedUserId) ?? throw new TaskNotFoundException();
            $task->assertManagedBy($authenticatedUserId);
            $now = $this->clock->now();
            $snapshot = $task->snapshot();

            $task->delete($now);
            $this->tasks->save($task);
            $this->history->add(TaskHistory::record($task, $authenticatedUserId, TaskHistoryAction::Deleted, $snapshot, null, $now));
        });
    }
}
