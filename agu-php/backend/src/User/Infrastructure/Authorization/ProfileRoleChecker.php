<?php

namespace App\User\Infrastructure\Authorization;

use App\Shared\Application\Port\RoleCheckerInterface;
use App\Shared\Domain\Exception\PermissionDeniedException;
use App\Shared\Domain\Role;
use App\User\Domain\Repository\ProfileRepositoryInterface;

/**
 * Implementa a porta RoleCheckerInterface: usuário → profile → roles, consultando o banco.
 *
 * Ler do banco (e não do JWT) faz uma mudança valer na hora: o token só prova quem é a pessoa, e é emitido por uma
 * hora. Trocar o profile de alguém, ou as roles de um profile, não espera o token expirar.
 */
final class ProfileRoleChecker implements RoleCheckerInterface
{
    public function __construct(private readonly ProfileRepositoryInterface $profiles)
    {
    }

    public function assertHas(int $userId, Role $role): void
    {
        $profile = $this->profiles->findByActiveUserId($userId);

        if (null === $profile || !$profile->has($role)) {
            throw new PermissionDeniedException($role);
        }
    }
}
