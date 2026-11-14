<?php

namespace App\Task\Domain\Event;

/**
 * O status de uma tarefa mudou. Como TaskAssigned, é uma "foto" em dados simples, que vai para uma fila.
 *
 * Idempotência: `eventId` identifica ESTA mudança (tarefa + instante em microssegundos) e é o mesmo se a
 * mensagem for entregue mais de uma vez; `occurredAt` ordena as mudanças de uma mesma tarefa. A mensagem
 * traz o estado novo (e não "avançar um passo"), então aplicá-la de novo não altera o resultado.
 */
final class TaskStatusChanged
{
    /**
     * @param list<int> $audience Quem enxerga a tarefa no momento da mudança (quem criou e o responsável).
     */
    public function __construct(
        public readonly string $eventId,
        public readonly int $taskId,
        public readonly string $status,
        public readonly string $previousStatus,
        public readonly ?string $completedAt,
        public readonly int $changedBy,
        public readonly array $audience,
        public readonly string $occurredAt,
    ) {
    }
}
