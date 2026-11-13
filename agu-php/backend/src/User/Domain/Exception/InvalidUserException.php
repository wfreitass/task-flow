<?php

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidUserException extends DomainException
{
    public static function invalidUsername(): self
    {
        return new self('USER_INVALID_USERNAME', 'O nome de usuário deve ter entre 3 e 50 caracteres.', ['username' => 'invalid']);
    }

    public static function invalidEmail(): self
    {
        return new self('USER_INVALID_EMAIL', 'O e-mail informado é inválido.', ['email' => 'invalid']);
    }
}
