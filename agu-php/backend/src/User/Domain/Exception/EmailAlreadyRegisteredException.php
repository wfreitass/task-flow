<?php

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

final class EmailAlreadyRegisteredException extends ConflictException
{
    public function __construct()
    {
        parent::__construct('EMAIL_ALREADY_REGISTERED', 'Este e-mail já está cadastrado.', ['email' => 'taken']);
    }
}
