<?php

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;

/**
 * Mensagem única para e-mail inexistente, senha errada e conta inativa: não revela qual deles ocorreu.
 */
final class InvalidCredentialsException extends UnauthorizedException
{
    public function __construct()
    {
        parent::__construct('INVALID_CREDENTIALS', 'E-mail ou senha inválidos.');
    }
}
