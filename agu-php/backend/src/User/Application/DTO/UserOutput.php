<?php

namespace App\User\Application\DTO;

use App\Shared\Domain\Role;
use App\User\Domain\Entity\Profile;
use App\User\Domain\Entity\User;

final class UserOutput
{
    /**
     * @param list<string> $roles as roles que o usuário tem, pelo profile dele
     */
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
        public readonly int $profileId,
        public readonly string $profileCode,
        public readonly string $profileName,
        public readonly array $roles,
    ) {
    }

    public static function fromUser(User $user, Profile $profile): self
    {
        return new self(
            (int) $user->id(),
            $user->username(),
            $user->email(),
            (int) $profile->id(),
            $profile->code(),
            $profile->name(),
            array_map(static fn (Role $role): string => $role->value, $profile->roles()),
        );
    }
}
