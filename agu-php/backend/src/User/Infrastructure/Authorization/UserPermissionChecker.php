<?php

namespace App\User\Infrastructure\Authorization;

use App\Shared\Application\Port\PermissionCheckerInterface;
use App\Shared\Domain\Exception\PermissionDeniedException;
use App\Shared\Domain\Permission;
use App\User\Domain\Repository\UserRepositoryInterface;

/**
 * Implementa a porta PermissionCheckerInterface consultando o perfil ATUAL do usuário no banco.
 *
 * Ler do banco (e não do JWT) faz uma mudança de perfil valer na hora: o token só prova quem é a pessoa, e é emitido
 * por uma hora. Rebaixar um admin não precisa esperar o token dele expirar.
 */
final class UserPermissionChecker implements PermissionCheckerInterface
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    public function assertCan(int $userId, Permission $permission): void
    {
        $user = $this->users->findById($userId);

        if (null === $user || !$user->isActive() || !$user->can($permission)) {
            throw new PermissionDeniedException($permission);
        }
    }
}
