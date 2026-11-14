<?php

namespace App\User\Application\Service;

use App\Shared\Application\Port\CurrentUserIdProviderInterface;
use App\User\Application\DTO\UserOutput;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\ProfileRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final class GetCurrentUserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly ProfileRepositoryInterface $profiles,
        private readonly CurrentUserIdProviderInterface $currentUser,
    ) {}

    public function execute(): UserOutput
    {
        $user = $this->users->findById($this->currentUser->currentUserId());


        if (null === $user || !$user->isActive()) {
            throw new UserNotFoundException();
        }

        $profile = $this->profiles->findById($user->profileId()) ?? throw new UserNotFoundException();

        return UserOutput::fromUser($user, $profile);
    }
}
