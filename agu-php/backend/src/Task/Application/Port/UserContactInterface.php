<?php

namespace App\Task\Application\Port;

use App\Task\Application\DTO\UserContact;

/**
 * Consulta o contato (inclui e-mail) de um usuário, para notificá-lo. É separada do UserDirectoryInterface,
 * que só entrega id e nome e é usada para montar respostas da API. Implementada pelo contexto User.
 */
interface UserContactInterface
{
    /**
     * O contato do usuário, se existir e estiver ativo.
     */
    public function findActiveContactById(int $id): ?UserContact;
}
