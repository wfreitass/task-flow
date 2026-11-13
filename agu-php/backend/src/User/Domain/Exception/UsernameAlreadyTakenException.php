<?php

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

final class UsernameAlreadyTakenException extends ConflictException
{
    public function __construct()
    {
        parent::__construct('USERNAME_ALREADY_TAKEN', 'Este nome de usuário já está em uso.', ['username' => 'taken']);
    }
}
