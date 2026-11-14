<?php

namespace App\Task\Infrastructure\Messaging;

use App\Task\Application\Service\IndexTaskService;
use App\Task\Domain\Event\TaskChanged;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Consumidor do evento TaskChanged: o worker o chama para atualizar o índice de busca. Só encaminha ao caso de uso.
 */
#[AsMessageHandler]
final class TaskChangedMessageHandler
{
    public function __construct(private readonly IndexTaskService $indexTask)
    {
    }

    public function __invoke(TaskChanged $event): void
    {
        $this->indexTask->execute($event);
    }
}
