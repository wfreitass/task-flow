<?php

namespace App\User\Application\DTO;

use App\Shared\Domain\Role;

final class CreateProfileInput
{
    /**
     * @param list<Role> $roles
     */
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description,
        public readonly array $roles,
    ) {
    }
}
