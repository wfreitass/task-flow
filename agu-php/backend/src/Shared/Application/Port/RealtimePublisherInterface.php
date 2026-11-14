<?php

namespace App\Shared\Application\Port;

use App\Shared\Application\DTO\RealtimeMessage;

/**
 * Entrega uma mensagem aos usuários conectados em tempo real (WebSocket). Não conhece o protocolo nem o
 * servidor: quem implementa decide como chegar lá.
 *
 * Deve ser seguro repetir a chamada com a mesma mensagem (mesmo `id`): a entrega não duplica.
 */
interface RealtimePublisherInterface
{
    /**
     * @throws \RuntimeException se não foi possível entregar o pedido ao servidor de tempo real
     */
    public function publish(RealtimeMessage $message): void;
}
