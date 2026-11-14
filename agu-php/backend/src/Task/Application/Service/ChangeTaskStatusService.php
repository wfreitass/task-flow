<?php

namespace App\Task\Application\Service;

use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\EventPublisherInterface;
use App\Shared\Application\Port\PermissionCheckerInterface;
use App\Shared\Application\Port\TransactionRunnerInterface;
use App\Shared\Domain\Permission;
use App\Task\Application\DTO\ChangeTaskStatusInput;
use App\Task\Application\DTO\TaskOutput;
use App\Task\Domain\Entity\TaskHistory;
use App\Task\Domain\Enum\TaskHistoryAction;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Event\TaskStatusChanged;
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
        private readonly EventPublisherInterface $events,
        private readonly PermissionCheckerInterface $permissions,
    ) {
    }

    /**
     * Muda o status respeitando as transições permitidas. Quem criou a tarefa e o responsável podem. Pedir o
     * status que a tarefa já tem não faz nada: sem gravação, sem histórico e sem evento (repetir o pedido é seguro).
     *
     * @throws \App\Task\Domain\Exception\InvalidStatusTransitionException
     */
    public function execute(ChangeTaskStatusInput $input, int $authenticatedUserId): TaskOutput
    {
        // Cancelar (status `canceled`) é uma permissão do perfil: o executor muda os demais status, mas não cancela.
        // Vale mesmo que a tarefa já esteja cancelada, para a resposta não depender do estado dela.
        if (TaskStatus::Canceled === $input->status) {
            $this->permissions->assertCan($authenticatedUserId, Permission::TaskCancel);
        }

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

                // Avisa quem enxerga a tarefa (publicado só depois do commit; ver EventPublisherInterface).
                $this->events->publish(new TaskStatusChanged(
                    eventId: sprintf('task.status_changed:%d:%s', (int) $task->id(), $now->format('Uu')),
                    taskId: (int) $task->id(),
                    status: $task->status()->value,
                    previousStatus: $previous->value,
                    completedAt: $task->completedAt()?->format(\DATE_ATOM),
                    changedBy: $authenticatedUserId,
                    audience: array_values(array_unique(array_filter(
                        [$task->createdBy(), $task->assignedTo()],
                        static fn (?int $id): bool => null !== $id,
                    ))),
                    occurredAt: $now->format('Y-m-d\TH:i:s.u\Z'),
                ));
            }

            return $this->outputs->one($task);
        });
    }
}
