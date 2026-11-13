<?php

namespace App\Task\Application\Service;

use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\TransactionRunnerInterface;
use App\Task\Application\DTO\ChangeTaskStatusInput;
use App\Task\Application\DTO\TaskOutput;
use App\Task\Domain\Entity\TaskHistory;
use App\Task\Domain\Enum\TaskHistoryAction;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskHistoryRepositoryInterface;
use App\Task\Domain\Repository\TaskRepositoryInterface;

final class ChangeTaskStatusService
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskHistoryRepositoryInterface $history,
        private readonly ClockInterface $clock,
        private readonly TransactionRunnerInterface $transactions,
        private readonly TaskOutputFactory $outputs,
    ) {
    }

    /**
     * Muda o status respeitando as transições permitidas. Quem criou a tarefa e o responsável podem. Pedir o
     * status que a tarefa já tem não faz nada.
     *
     * @throws \App\Task\Domain\Exception\InvalidStatusTransitionException
     */
    public function execute(ChangeTaskStatusInput $input, int $authenticatedUserId): TaskOutput
    {
        return $this->transactions->run(function () use ($input, $authenticatedUserId): TaskOutput {
            $task = $this->tasks->findAccessibleById($input->taskId, $authenticatedUserId) ?? throw new TaskNotFoundException();
            $now = $this->clock->now();
            $previous = $task->status();
            $previousCompletedAt = $task->completedAt();

            if ($task->changeStatus($input->status, $now)) {
                $this->tasks->save($task);
                $this->history->add(TaskHistory::record(
                    $task,
                    $authenticatedUserId,
                    TaskHistoryAction::StatusChanged,
                    ['status' => $previous->value, 'completed_at' => $previousCompletedAt?->format(\DATE_ATOM)],
                    ['status' => $task->status()->value, 'completed_at' => $task->completedAt()?->format(\DATE_ATOM)],
                    $now,
                ));
            }

            return $this->outputs->one($task);
        });
    }
}
