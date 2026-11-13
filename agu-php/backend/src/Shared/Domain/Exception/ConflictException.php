<?php

namespace App\Shared\Domain\Exception;

/**
 * Categoria: a operação conflita com o estado atual (ex.: valor já cadastrado). Vira HTTP 409.
 */
abstract class ConflictException extends DomainException
{
}
