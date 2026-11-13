<?php

namespace App\Task\Application\Service;

use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\TransactionRunnerInterface;
use App\Task\Application\DTO\CreateTaskInput;
use App\Task\Application\DTO\TaskOutput;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Entity\TaskHistory;
use App\Task\Domain\Enum\TaskHistoryAction;
use App\Task\Domain\Repository\TaskHistoryRepositoryInterface;
use App\Task\Domain\Repository\TaskRepositoryInterface;

final class CreateTaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskHistoryRepositoryInterface $history,
        private readonly ClockInterface $clock,
        private readonly TransactionRunnerInterface $transactions,
    ) {
    }

    /**
     * Cria a tarefa e registra o histórico na mesma transação.
     * O criador é sempre o usuário autenticado, nunca um valor vindo do corpo da requisição.
     */
    public function execute(CreateTaskInput $input, int $authenticatedUserId): TaskOutput
    {
        return $this->transactions->run(function () use ($input, $authenticatedUserId): TaskOutput {
            $now = $this->clock->now();

            $task = Task::create(
                $authenticatedUserId,
                $input->title,
                $input->description,
                $input->priority,
                $input->dueDate,
                $now,
            );
            $this->tasks->add($task);

            $this->history->add(TaskHistory::record(
                $task,
                $authenticatedUserId,
                TaskHistoryAction::Created,
                null,
                $task->snapshot(),
                $now,
            ));

            return TaskOutput::fromTask($task);
        });
    }
}
