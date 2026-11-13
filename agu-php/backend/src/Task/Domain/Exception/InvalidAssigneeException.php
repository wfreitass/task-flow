<?php

namespace App\Task\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidAssigneeException extends DomainException
{
    /**
     * Usuário inexistente ou inativo: não se diferencia, para não revelar o estado de contas alheias.
     */
    public static function notFound(int $userId): self
    {
        return new self('ASSIGNEE_NOT_FOUND', 'O usuário escolhido como responsável não existe ou está inativo.', ['assignedTo' => $userId]);
    }
}
