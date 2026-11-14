<?php

namespace App\Shared\Application\Port;

use App\Shared\Domain\Exception\PermissionDeniedException;
use App\Shared\Domain\Permission;

/**
 * Confere se o perfil (papel) de um usuário permite uma ação. Quem sabe os perfis é o contexto User; os demais
 * contextos só perguntam por esta porta.
 */
interface PermissionCheckerInterface
{
    /**
     * @throws PermissionDeniedException se o usuário não existe, está inativo ou o perfil dele não tem a permissão
     */
    public function assertCan(int $userId, Permission $permission): void;
}
