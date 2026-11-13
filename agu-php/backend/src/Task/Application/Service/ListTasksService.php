<?php

namespace App\Task\Application\Service;

use App\Task\Application\DTO\ListTasksInput;
use App\Task\Application\DTO\TaskOutput;
use App\Task\Application\DTO\TaskPageOutput;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Repository\TaskCriteria;
use App\Task\Domain\Repository\TaskRepositoryInterface;

final class ListTasksService
{
    public function __construct(private readonly TaskRepositoryInterface $tasks)
    {
    }

    /**
     * Lista só as tarefas do usuário autenticado, com filtros, ordenação e paginação.
     */
    public function execute(ListTasksInput $input, int $authenticatedUserId): TaskPageOutput
    {
        $criteria = new TaskCriteria(
            ownerId: $authenticatedUserId,
            statuses: $input->statuses,
            priorities: $input->priorities,
            search: $input->search,
            dueFrom: $input->dueFrom,
            dueTo: $input->dueTo,
            sortBy: $input->sortBy,
            direction: $input->direction,
            page: $input->page,
            perPage: $input->perPage,
        );

        $page = $this->tasks->search($criteria);

        return new TaskPageOutput(
            array_map(static fn (Task $task): TaskOutput => TaskOutput::fromTask($task), $page->tasks),
            $page->total,
            $criteria->page,
            $criteria->perPage,
        );
    }
}
