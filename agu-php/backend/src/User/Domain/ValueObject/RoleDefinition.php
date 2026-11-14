<?php

namespace App\User\Domain\ValueObject;

use App\Shared\Domain\Role;

/**
 * Uma role do catálogo (tabela `roles`), com o nome e a descrição para mostrar ao usuário.
 */
final class RoleDefinition
{
    public function __construct(
        public readonly Role $role,
        public readonly string $name,
        public readonly ?string $description,
    ) {
    }
}
