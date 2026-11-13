<?php

namespace App\Shared\Infrastructure\Messaging;

use App\Shared\Application\Port\EventPublisherInterface;
use App\Shared\Infrastructure\Persistence\AfterCommitActions;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Implementa a porta EventPublisherInterface com o Symfony Messenger. Para onde cada evento vai (RabbitMQ, memória
 * nos testes) está em config/packages/messenger.yaml, em `routing`.
 *
 * A publicação é adiada até o commit do banco (AfterCommitActions): publicar antes deixaria o consumidor
 * receber o evento de uma alteração que ainda não existe, ou que depois seria desfeita. Se o broker estiver
 * fora do ar, a falha é registrada no log e a operação que gerou o evento segue válida.
 */
final class MessengerEventPublisher implements EventPublisherInterface
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly AfterCommitActions $afterCommit,
    ) {
    }

    public function publish(object $event): void
    {
        $this->afterCommit->execute(fn () => $this->bus->dispatch($event));
    }
}
