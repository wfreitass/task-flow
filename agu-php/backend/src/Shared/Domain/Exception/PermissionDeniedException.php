<?php

namespace App\Shared\Domain\Exception;

use App\Shared\Domain\Permission;

/**
 * O perfil do usuário não tem a permissão que a operação exige. Vira HTTP 403.
 */
final class PermissionDeniedException extends ForbiddenException
{
    public function __construct(Permission $permission)
    {
        parent::__construct(
            'PERMISSION_DENIED',
            'Seu perfil não tem permissão para esta operação.',
            ['permission' => $permission->value],
        );
    }
}
