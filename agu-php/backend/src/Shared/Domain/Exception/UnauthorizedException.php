<?php

namespace App\Shared\Domain\Exception;

/**
 * Categoria: a identidade não pôde ser comprovada (ex.: credenciais inválidas). Vira HTTP 401.
 */
abstract class UnauthorizedException extends DomainException
{
}
