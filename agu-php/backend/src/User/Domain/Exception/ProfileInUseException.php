<?php

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

final class ProfileInUseException extends ConflictException
{
    public function __construct(int $users)
    {
        parent::__construct('PROFILE_IN_USE', 'Não é possível excluir um perfil que ainda tem usuários. Mude o perfil deles antes.', ['users' => $users]);
    }
}
