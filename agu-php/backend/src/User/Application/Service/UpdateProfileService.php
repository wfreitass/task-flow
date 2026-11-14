<?php

namespace App\User\Application\Service;

use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\RoleCheckerInterface;
use App\Shared\Application\Port\TransactionRunnerInterface;
use App\Shared\Domain\Role;
use App\User\Application\DTO\ProfileOutput;
use App\User\Application\DTO\UpdateProfileInput;
use App\User\Domain\Exception\ProfileNotFoundException;
use App\User\Domain\Repository\ProfileRepositoryInterface;

final class UpdateProfileService
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profiles,
        private readonly RoleCheckerInterface $roles,
        private readonly TransactionRunnerInterface $transactions,
        private readonly ManagerGuard $guard,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Muda o nome, a descrição e/ou as roles de um profile. Vale na hora para todos os usuários que o têm. Exige a
     * role `profile.manage`. Tirar `user.manage` do profile que deixaria o sistema sem quem gerencie usuários é
     * recusado (409 `LAST_ADMIN`).
     */
    public function execute(int $actorId, UpdateProfileInput $input): ProfileOutput
    {
        $this->roles->assertHas($actorId, Role::ProfileManage);

        return $this->transactions->run(fn (): ProfileOutput => $this->guard->protect(function () use ($input): ProfileOutput {
            $profile = $this->profiles->findById($input->profileId) ?? throw new ProfileNotFoundException();
            $now = $this->clock->now();

            $profile->changeDetails(
                $input->name ?? $profile->name(),
                $input->changeDescription ? $input->description : $profile->description(),
                $now,
            );
            if (null !== $input->roles) {
                $profile->replaceRoles($input->roles, $now);
            }

            $this->profiles->save($profile);

            return ProfileOutput::fromProfile($profile);
        }));
    }
}
