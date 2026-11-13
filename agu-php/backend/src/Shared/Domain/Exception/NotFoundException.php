<?php

namespace App\Shared\Domain\Exception;

/**
 * Categoria: o recurso pedido não existe (ou não é visível para quem pede). Vira HTTP 404.
 */
abstract class NotFoundException extends DomainException
{
}
