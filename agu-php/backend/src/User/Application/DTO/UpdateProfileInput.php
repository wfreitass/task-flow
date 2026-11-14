<?php

namespace App\User\Application\DTO;

use App\Shared\Domain\Role;

/**
 * Alteração parcial de um profile: o que vier null não muda. A descrição tem um sinalizador próprio, porque
 * "sem descrição" (null) é um valor válido que o cliente pode querer gravar.
 */
final class UpdateProfileInput
{
    /**
     * @param list<Role>|null $roles null = não mexe nas roles
     */
    public function __construct(
        public readonly int $profileId,
        public readonly ?string $name,
        public readonly bool $changeDescription,
        public readonly ?string $description,
        public readonly ?array $roles,
    ) {
    }
}
