<?php

namespace App\Task\Infrastructure\Messaging;

use App\Task\Application\Service\BroadcastTaskStatusChangedService;
use App\Task\Domain\Event\TaskStatusChanged;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Consumidor do evento TaskStatusChanged: o worker o chama quando a mensagem sai da fila e o caso de uso
 * repassa ao servidor de WebSocket. Só encaminha; as regras estão na camada de aplicação.
 */
#[AsMessageHandler]
final class TaskStatusChangedMessageHandler
{
    public function __construct(private readonly BroadcastTaskStatusChangedService $broadcast)
    {
    }

    public function __invoke(TaskStatusChanged $event): void
    {
        $this->broadcast->execute($event);
    }
}
