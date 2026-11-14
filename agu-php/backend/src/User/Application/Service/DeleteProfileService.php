<?php

namespace App\User\Application\Service;

use App\Shared\Application\Port\RoleCheckerInterface;
use App\Shared\Application\Port\TransactionRunnerInterface;
use App\Shared\Domain\Role;
use App\User\Domain\Exception\ProfileInUseException;
use App\User\Domain\Exception\ProfileNotFoundException;
use App\User\Domain\Exception\SystemProfileProtectedException;
use App\User\Domain\Repository\ProfileRepositoryInterface;

final class DeleteProfileService
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profiles,
        private readonly RoleCheckerInterface $roles,
        private readonly TransactionRunnerInterface $transactions,
    ) {
    }

    /**
     * Exclui um profile. Exige `profile.manage`. Não exclui os de sistema (admin e executor) nem um profile que
     * ainda tem usuários: seria deixá-los sem roles.
     */
    public function execute(int $actorId, int $profileId): void
    {
        $this->roles->assertHas($actorId, Role::ProfileManage);

        $this->transactions->run(function () use ($profileId): void {
            $profile = $this->profiles->findById($profileId) ?? throw new ProfileNotFoundException();

            if ($profile->isSystem()) {
                throw new SystemProfileProtectedException();
            }
            $users = $this->profiles->countUsers($profileId);
            if ($users > 0) {
                throw new ProfileInUseException($users);
            }

            $this->profiles->remove($profile);
        });
    }
}
