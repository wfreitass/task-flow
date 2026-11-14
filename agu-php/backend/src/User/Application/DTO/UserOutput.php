<?php

namespace App\User\Application\DTO;

use App\User\Domain\Entity\User;

final class UserOutput
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
        public readonly string $role,
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self((int) $user->id(), $user->username(), $user->email(), $user->role()->value);
    }
}
