<?php

namespace App\Shared\Domain\Exception;

use App\Shared\Domain\Role;

/**
 * O profile do usuário não tem a role que a operação exige. Vira HTTP 403.
 */
final class PermissionDeniedException extends ForbiddenException
{
    public function __construct(Role $role)
    {
        parent::__construct(
            'PERMISSION_DENIED',
            'Seu perfil não tem permissão para esta operação.',
            ['role' => $role->value],
        );
    }
}
