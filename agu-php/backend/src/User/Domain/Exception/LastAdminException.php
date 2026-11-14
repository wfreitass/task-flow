<?php

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

/**
 * A mudança deixaria o sistema sem nenhum usuário ativo que possa gerenciar usuários (role `user.manage`): ninguém
 * mais conseguiria vincular perfis. Vale para rebaixar um usuário e para tirar a role de um perfil.
 */
final class LastAdminException extends ConflictException
{
    public function __construct()
    {
        parent::__construct('LAST_ADMIN', 'A mudança deixaria o sistema sem nenhum usuário ativo que possa gerenciar usuários.');
    }
}
