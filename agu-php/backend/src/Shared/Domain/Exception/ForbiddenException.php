<?php

namespace App\Shared\Domain\Exception;

/**
 * Categoria: quem pede existe e enxerga o recurso, mas não pode fazer esta operação. Vira HTTP 403.
 */
abstract class ForbiddenException extends DomainException
{
}
