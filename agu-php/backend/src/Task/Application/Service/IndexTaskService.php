<?php

namespace App\Task\Application\Service;

use App\Task\Application\Port\TaskSearchIndexInterface;
use App\Task\Domain\Event\TaskChanged;
use App\Task\Domain\Repository\TaskRepositoryInterface;

/**
 * Mantém o índice de busca em dia: relê a tarefa e grava no índice o estado ATUAL dela (ou a retira, se foi
 * excluída). Por não depender do conteúdo do evento, é idempotente e imune à ordem: duas mensagens da mesma
 * tarefa, em qualquer ordem ou repetidas, terminam no mesmo estado.
 */
final class IndexTaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskSearchIndexInterface $index,
    ) {
    }

    public function execute(TaskChanged $event): void
    {
        $task = $this->tasks->findById($event->taskId);

        if (null === $task || $task->isDeleted()) {
            $this->index->remove($event->taskId);

            return;
        }

        $this->index->index($task);
    }
}
