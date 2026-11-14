<?php

namespace App\Task\Domain\Event;

/**
 * Os dados de uma tarefa mudaram (criada, editada, atribuída, com novo status ou excluída). Serve para manter o
 * índice de busca em dia. Só leva o id, e não os dados: quem consome relê a tarefa e indexa o estado ATUAL, então
 * receber o evento repetido ou fora de ordem dá sempre o mesmo resultado (idempotente).
 */
final class TaskChanged
{
    public function __construct(
        public readonly int $taskId,
        public readonly string $occurredAt,
    ) {
    }
}
