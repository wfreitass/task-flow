<?php

namespace App\User\Application\Service;

use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\RoleCheckerInterface;
use App\Shared\Application\Port\TransactionRunnerInterface;
use App\Shared\Domain\Role;
use App\User\Application\DTO\UserOutput;
use App\User\Domain\Entity\Profile;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\ProfileNotFoundException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\ProfileRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

/**
 * Vincula um profile a um usuário. Duas portas de entrada para o mesmo trabalho:
 *  - asUser(): pela API; exige a role `user.manage`;
 *  - byEmail(): pelo comando `app:user:set-profile`, para criar o primeiro admin. Quem roda o comando já tem acesso
 *    ao servidor, então não há usuário logado a conferir.
 *
 * O sistema nunca fica sem um usuário ativo com `user.manage` (ManagerGuard).
 */
final class ChangeUserProfileService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly ProfileRepositoryInterface $profiles,
        private readonly RoleCheckerInterface $roles,
        private readonly TransactionRunnerInterface $transactions,
        private readonly ManagerGuard $guard,
        private readonly ClockInterface $clock,
    ) {
    }

    public function asUser(int $actorId, int $targetId, int $profileId): UserOutput
    {
        $this->roles->assertHas($actorId, Role::UserManage);

        return $this->apply(
            fn (): ?User => $this->users->findById($targetId),
            fn (): ?Profile => $this->profiles->findById($profileId),
        );
    }

    public function byEmail(string $email, string $profileCode): UserOutput
    {
        return $this->apply(
            fn (): ?User => $this->users->findByEmail($email),
            fn (): ?Profile => $this->profiles->findByCode($profileCode),
        );
    }

    /**
     * Pelo código do profile (usado pelo endpoint antigo `PATCH /users/{id}/role`, mantido por compatibilidade).
     */
    public function asUserByProfileCode(int $actorId, int $targetId, string $profileCode): UserOutput
    {
        $this->roles->assertHas($actorId, Role::UserManage);

        return $this->apply(
            fn (): ?User => $this->users->findById($targetId),
            fn (): ?Profile => $this->profiles->findByCode($profileCode),
        );
    }

    /**
     * @param callable(): ?User    $findUser
     * @param callable(): ?Profile $findProfile
     */
    private function apply(callable $findUser, callable $findProfile): UserOutput
    {
        return $this->transactions->run(fn (): UserOutput => $this->guard->protect(function () use ($findUser, $findProfile): UserOutput {
            $user = $findUser() ?? throw new UserNotFoundException();
            $profile = $findProfile() ?? throw new ProfileNotFoundException();

            if ($user->changeProfile((int) $profile->id(), $this->clock->now())) {
                $this->users->save($user);
            }

            return UserOutput::fromUser($user, $profile);
        }));
    }
}
