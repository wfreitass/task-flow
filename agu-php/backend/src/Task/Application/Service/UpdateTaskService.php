<?php

namespace App\Task\Application\Service;

use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\TransactionRunnerInterface;
use App\Task\Application\DTO\TaskOutput;
use App\Task\Application\DTO\UpdateTaskInput;
use App\Task\Application\Port\UserDirectoryInterface;
use App\Task\Domain\Entity\TaskHistory;
use App\Task\Domain\Enum\TaskHistoryAction;
use App\Task\Domain\Exception\InvalidAssigneeException;
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
        private readonly UserDirectoryInterface $directory,
        private readonly TaskOutputFactory $outputs,
    ) {
    }

    /**
     * Altera os dados e o responsável da tarefa (não o status). Só quem criou a tarefa pode. Só grava, e só
     * registra histórico, se algo realmente mudou.
     */
    public function execute(UpdateTaskInput $input, int $authenticatedUserId): TaskOutput
    {
        return $this->transactions->run(function () use ($input, $authenticatedUserId): TaskOutput {
            $task = $this->tasks->findAccessibleById($input->taskId, $authenticatedUserId) ?? throw new TaskNotFoundException();
            $task->assertManagedBy($authenticatedUserId);
            $now = $this->clock->now();

            $changes = $task->changeDetails(
                $input->has('title') ? (string) $input->title : $task->title(),
                $input->has('description') ? $input->description : $task->description(),
                $input->has('priority') && null !== $input->priority ? $input->priority : $task->priority(),
                $input->has('startDate') ? $input->startDate : $task->startDate(),
                $input->has('dueDate') ? $input->dueDate : $task->dueDate(),
                $now,
            );

            $assignment = ['old' => [], 'new' => []];
            if ($input->has('assignedTo')) {
                // Reenviar o responsável atual não exige que ele ainda esteja ativo; trocar por outro, sim.
                if (null !== $input->assignedTo && $input->assignedTo !== $task->assignedTo() && null === $this->directory->findActiveById($input->assignedTo)) {
                    throw InvalidAssigneeException::notFound($input->assignedTo);
                }
                $assignment = $task->assignTo($input->assignedTo, $now);
            }

            if ([] !== $changes['new'] || [] !== $assignment['new']) {
                $this->tasks->save($task);
            }
            if ([] !== $changes['new']) {
                $this->history->add(TaskHistory::record($task, $authenticatedUserId, TaskHistoryAction::Updated, $changes['old'], $changes['new'], $now));
            }
            if ([] !== $assignment['new']) {
                $this->history->add(TaskHistory::record($task, $authenticatedUserId, TaskHistoryAction::Assigned, $assignment['old'], $assignment['new'], $now));
            }

            return $this->outputs->one($task);
        });
    }
}
