<?php

namespace App\Task\Application\Port;

use App\Task\Application\DTO\TaskSearchResult;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Repository\TaskCriteria;

/**
 * Índice de busca textual de tarefas (hoje, OpenSearch). É uma cópia derivada do banco, só para pesquisar: o
 * banco continua sendo a fonte de verdade, e o índice pode ser refeito por inteiro (ReindexTasksService).
 *
 * Todas as operações de escrita são idempotentes: indexar a mesma tarefa de novo apenas sobrescreve o documento.
 */
interface TaskSearchIndexInterface
{
    /**
     * @throws \RuntimeException se o índice estiver indisponível
     */
    public function index(Task $task): void;

    /**
     * Retira a tarefa do índice. Não é erro se ela já não estiver lá.
     *
     * @throws \RuntimeException se o índice estiver indisponível
     */
    public function remove(int $taskId): void;

    /**
     * Procura por `$criteria->search` entre as tarefas que o usuário enxerga, aplicando os mesmos filtros,
     * ordenação e paginação da listagem.
     *
     * @throws \RuntimeException se o índice estiver indisponível ou não existir
     */
    public function search(TaskCriteria $criteria): TaskSearchResult;

    /**
     * Indexa várias tarefas de uma vez.
     *
     * @param list<Task> $tasks
     *
     * @throws \RuntimeException se o índice estiver indisponível
     */
    public function indexMany(array $tasks): void;

    public function exists(): bool;

    /**
     * Cria o índice com o mapeamento da aplicação, se ainda não existir. Com `$recreate`, apaga o atual antes.
     */
    public function ensure(bool $recreate = false): void;
}
