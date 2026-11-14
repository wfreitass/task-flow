<?php

namespace App\Shared\Application\DTO;

/**
 * Mensagem de tempo real, entregue aos usuários conectados por WebSocket.
 *
 * `id` é a chave de idempotência: a mesma mudança sempre tem o mesmo id, então quem recebe (o servidor
 * WebSocket e o cliente) descarta repetições. `occurredAt` permite ao cliente ignorar uma mensagem mais velha
 * que o estado que ele já tem.
 */
final class RealtimeMessage
{
    /**
     * @param list<int>            $recipientIds ids dos usuários que devem receber
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $occurredAt,
        public readonly array $recipientIds,
        public readonly array $data,
    ) {
    }
}
