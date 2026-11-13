<?php

namespace App\User\Application\Service;

use App\User\Application\DTO\UserSummaryOutput;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;

final class ListUsersService
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    /** @return list<UserSummaryOutput> */
    public function execute(): array
    {
        return array_map(static fn (User $user): UserSummaryOutput => UserSummaryOutput::fromUser($user), $this->users->findActive());
    }
}
