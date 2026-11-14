<?php

namespace App\Task\Application\Service;

use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\PermissionCheckerInterface;
use App\Shared\Application\Port\TransactionRunnerInterface;
use App\Shared\Domain\Permission;
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
        private readonly PermissionCheckerInterface $permissions,
    ) {
    }

    /**
     * Exclusão lógica: a tarefa some das consultas, mas o registro e o histórico permanecem. Só quem criou a tarefa
     * pode excluí-la; o responsável não. Além disso, o PERFIL precisa permitir (só admin; o executor não exclui,
     * pois excluir é cancelar).
     */
    public function execute(int $taskId, int $authenticatedUserId): void
    {
        // O perfil é conferido antes de olhar a tarefa: negar por perfil não revela se a tarefa existe.
        $this->permissions->assertCan($authenticatedUserId, Permission::TaskDelete);

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
