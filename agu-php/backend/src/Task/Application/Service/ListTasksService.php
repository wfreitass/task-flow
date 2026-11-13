<?php

namespace App\Task\Application\Service;

use App\Task\Application\DTO\ListTasksInput;
use App\Task\Application\DTO\TaskPageOutput;
use App\Task\Domain\Repository\TaskCriteria;
use App\Task\Domain\Repository\TaskRepositoryInterface;

final class ListTasksService
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskOutputFactory $outputs,
    ) {
    }

    /**
     * Lista as tarefas que o usuário autenticado criou ou de que é responsável (conforme o escopo), com filtros,
     * ordenação e paginação.
     */
    public function execute(ListTasksInput $input, int $authenticatedUserId): TaskPageOutput
    {
        $criteria = new TaskCriteria(
            viewerId: $authenticatedUserId,
            scope: $input->scope,
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
            $this->outputs->many($page->tasks),
            $page->total,
            $criteria->page,
            $criteria->perPage,
        );
    }
}
