<?php

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class UserNotFoundException extends NotFoundException
{
    public function __construct()
    {
        parent::__construct('USER_NOT_FOUND', 'Usuário não encontrado.');
    }
}
