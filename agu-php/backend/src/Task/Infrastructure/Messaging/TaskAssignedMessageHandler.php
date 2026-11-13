<?php

namespace App\Task\Infrastructure\Messaging;

use App\Task\Application\Service\NotifyTaskAssigneeService;
use App\Task\Domain\Event\TaskAssigned;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Consumidor do evento TaskAssigned: o worker (messenger:consume) o chama quando a mensagem sai da fila.
 * Só encaminha para o caso de uso; as regras estão na camada de aplicação, que não conhece o Messenger.
 */
#[AsMessageHandler]
final class TaskAssignedMessageHandler
{
    public function __construct(private readonly NotifyTaskAssigneeService $notifyAssignee)
    {
    }

    public function __invoke(TaskAssigned $event): void
    {
        $this->notifyAssignee->execute($event);
    }
}
