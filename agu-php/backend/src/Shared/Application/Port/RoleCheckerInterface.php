<?php

namespace App\Shared\Application\Port;

use App\Shared\Domain\Exception\PermissionDeniedException;
use App\Shared\Domain\Role;

/**
 * Confere se o profile de um usuário tem uma role. Quem sabe os profiles é o contexto User; os demais contextos só
 * perguntam por esta porta.
 */
interface RoleCheckerInterface
{
    /**
     * @throws PermissionDeniedException se o usuário não existe, está inativo ou o profile dele não tem a role
     */
    public function assertHas(int $userId, Role $role): void;
}
