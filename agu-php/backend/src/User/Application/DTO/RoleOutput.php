<?php

namespace App\User\Application\DTO;

use App\User\Domain\ValueObject\RoleDefinition;

final class RoleOutput
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description,
    ) {
    }

    public static function fromDefinition(RoleDefinition $definition): self
    {
        return new self($definition->role->value, $definition->name, $definition->description);
    }
}
