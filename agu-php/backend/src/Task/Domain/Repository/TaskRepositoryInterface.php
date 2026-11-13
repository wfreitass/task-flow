<?php

namespace App\Task\Domain\Repository;

use App\Task\Domain\Entity\Task;

interface TaskRepositoryInterface
{
    /**
     * Persiste uma nova tarefa. Após a chamada, a tarefa tem id.
     */
    public function add(Task $task): void;

    /**
     * Grava as alterações de uma tarefa que já existe (inclusive a exclusão lógica).
     */
    public function save(Task $task): void;

    /**
     * Busca uma tarefa **do dono informado** que não foi excluída. Tarefa inexistente, excluída ou de outro
     * usuário devolve null: o acesso é filtrado na própria consulta, nunca depois de carregar o registro.
     */
    public function findOwnedById(int $id, int $ownerId): ?Task;

    public function search(TaskCriteria $criteria): TaskPage;
}
