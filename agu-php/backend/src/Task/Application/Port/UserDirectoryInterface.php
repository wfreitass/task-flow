<?php

namespace App\Task\Application\Port;

use App\Task\Application\DTO\DirectoryUser;

/**
 * Consulta de usuários para o contexto Task, que só conhece usuários por id. Implementada pelo
 * contexto User (Infrastructure).
 */
interface UserDirectoryInterface
{
    /**
     * O usuário, se existir e estiver ativo. Serve para validar quem pode ser responsável por uma tarefa.
     */
    public function findActiveById(int $id): ?DirectoryUser;

    /**
     * Usuários por id, para exibir nomes (ativos ou não). Ids que não existem ficam de fora.
     *
     * @param list<int> $ids
     *
     * @return array<int, DirectoryUser> indexado pelo id
     */
    public function findByIds(array $ids): array;
}
