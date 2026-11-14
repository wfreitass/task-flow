<?php

namespace App\Task\Application\DTO;

/**
 * Resposta do índice de busca: só os ids, na ordem de relevância/ordenação pedida, e o total de resultados.
 * Os dados da tarefa vêm do banco (fonte de verdade), não do índice.
 */
final class TaskSearchResult
{
    /**
     * @param list<int> $ids   ids da página pedida
     * @param int       $total total de tarefas que atendem à busca, em todas as páginas
     */
    public function __construct(
        public readonly array $ids,
        public readonly int $total,
    ) {
    }
}
