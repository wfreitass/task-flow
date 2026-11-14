<?php

namespace App\User\Application\Service;

use App\Shared\Application\Port\RoleCheckerInterface;
use App\Shared\Domain\Role;
use App\User\Application\DTO\ProfileOutput;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\ProfileRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final class GetUserProfileService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly ProfileRepositoryInterface $profiles,
        private readonly RoleCheckerInterface $roles,
    ) {}

    public function execute(int $actorId, int $targetId): ProfileOutput
    {
        $this->roles->assertHas($actorId, Role::UserManage);
        $user = $this->users->findById($targetId) ?? throw new UserNotFoundException();
        $profile = $this->profiles->findById($user->profileId()) ?? throw new UserNotFoundException();
        return ProfileOutput::fromProfile($profile);
    }
}
