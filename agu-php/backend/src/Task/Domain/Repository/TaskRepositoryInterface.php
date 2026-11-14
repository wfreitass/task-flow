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

    /**
     * Busca uma tarefa pelo id, sem filtrar por usuário e inclusive se estiver excluída. Para processos internos
     * (como a indexação), nunca para atender a um usuário: para isso, use findAccessibleById.
     */
    public function findById(int $id): ?Task;

    /**
     * As tarefas que o usuário enxerga e que não foram excluídas, entre os ids dados, NA ORDEM dos ids.
     * Id inexistente, excluído ou sem relação com o usuário é simplesmente omitido.
     *
     * @param list<int> $ids
     *
     * @return list<Task>
     */
    public function findAccessibleByIds(array $ids, int $userId): array;

    /**
     * Um lote de tarefas não excluídas, em ordem crescente de id, com id maior que `$afterId`. Para percorrer
     * todas as tarefas (reindexação).
     *
     * @return list<Task>
     */
    public function findActiveBatchAfterId(int $afterId, int $limit): array;
}
