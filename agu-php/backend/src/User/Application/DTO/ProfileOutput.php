<?php

namespace App\User\Application\DTO;

use App\Shared\Domain\Role;
use App\User\Domain\Entity\Profile;

final class ProfileOutput
{
    /**
     * @param list<string> $roles códigos das roles do profile
     */
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $isSystem,
        public readonly array $roles,
    ) {
    }

    public static function fromProfile(Profile $profile): self
    {
        return new self(
            (int) $profile->id(),
            $profile->code(),
            $profile->name(),
            $profile->description(),
            $profile->isSystem(),
            array_map(static fn (Role $role): string => $role->value, $profile->roles()),
        );
    }
}
