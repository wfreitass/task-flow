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
     * Busca uma tarefa que o usuário enxerga (criou ou é o responsável) e que não foi excluída. Tarefa
     * inexistente, excluída ou sem relação com o usuário devolve null: o acesso é filtrado na própria
     * consulta, nunca depois de carregar o registro. O que o usuário pode FAZER com ela é regra do domínio
     * (Task::assertManagedBy).
     */
    public function findAccessibleById(int $id, int $userId): ?Task;

    public function search(TaskCriteria $criteria): TaskPage;
}
