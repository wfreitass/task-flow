<?php

namespace App\User\Application\Service;

use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\PermissionCheckerInterface;
use App\Shared\Application\Port\TransactionRunnerInterface;
use App\Shared\Domain\Permission;
use App\User\Application\DTO\UserOutput;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use App\User\Domain\Exception\LastAdminException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;

/**
 * Muda o perfil de um usuário (admin ou executor). Duas portas de entrada para o mesmo trabalho:
 *  - asUser(): pela API; só quem tem a permissão `user.manage` (admin) pode;
 *  - byEmail(): pelo comando `app:user:set-role`, para criar o primeiro admin. Quem roda o comando já tem
 *    acesso ao servidor, então não há usuário logado a conferir.
 *
 * O último admin ativo não pode ser rebaixado (LastAdminException).
 */
final class ChangeUserRoleService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PermissionCheckerInterface $permissions,
        private readonly TransactionRunnerInterface $transactions,
        private readonly ClockInterface $clock,
    ) {
    }

    public function asUser(int $actorId, int $targetId, UserRole $role): UserOutput
    {
        $this->permissions->assertCan($actorId, Permission::UserManage);

        return $this->apply(fn (): ?User => $this->users->findById($targetId), $role);
    }

    public function byEmail(string $email, UserRole $role): UserOutput
    {
        return $this->apply(fn (): ?User => $this->users->findByEmail($email), $role);
    }

    /**
     * @param callable(): ?User $find
     */
    private function apply(callable $find, UserRole $role): UserOutput
    {
        return $this->transactions->run(function () use ($find, $role): UserOutput {
            // Dentro da transação e com as linhas dos admins travadas: dois rebaixamentos ao mesmo tempo não
            // deixam o sistema sem admin.
            $activeAdmins = $this->users->countActiveAdminsForUpdate();

            $user = $find() ?? throw new UserNotFoundException();

            $demotingLastAdmin = $user->isAdmin() && $user->isActive() && UserRole::Admin !== $role && $activeAdmins <= 1;
            if ($demotingLastAdmin) {
                throw new LastAdminException();
            }

            if ($user->changeRole($role, $this->clock->now())) {
                $this->users->save($user);
            }

            return UserOutput::fromUser($user);
        });
    }
}
