<?php

namespace App\Shared\Presentation\Http\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Falha na validação de formato da entrada (Request DTO).
 */
final class ValidationFailedException extends \RuntimeException
{
    public function __construct(public readonly ConstraintViolationListInterface $violations)
    {
        parent::__construct('Dados inválidos.');
    }
}
