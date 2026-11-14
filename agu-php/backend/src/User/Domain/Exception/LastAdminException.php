<?php

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

/**
 * Tirar o perfil de admin do último admin ativo deixaria o sistema sem ninguém que gerencie usuários.
 */
final class LastAdminException extends ConflictException
{
    public function __construct()
    {
        parent::__construct('LAST_ADMIN', 'Não é possível rebaixar o último administrador ativo.');
    }
}
