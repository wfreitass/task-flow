<?php

namespace App\Shared\Application\Port;

/**
 * Publica um evento para ser tratado **de forma assíncrona**, fora da requisição (hoje, numa fila do RabbitMQ).
 * A aplicação só conhece esta interface. Implementada em Shared/Infrastructure/Messaging.
 *
 * Garantia: o evento só sai depois que a transação do banco é confirmada (se houver uma em andamento). Se a
 * publicação falhar, a operação que a originou não é desfeita: a falha vai para o log.
 */
interface EventPublisherInterface
{
    public function publish(object $event): void;
}
