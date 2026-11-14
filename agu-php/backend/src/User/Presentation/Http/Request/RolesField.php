<?php

namespace App\User\Presentation\Http\Request;

use App\Shared\Domain\Role;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Validação e conversão do campo `roles` (lista de códigos de role) dos pedidos de profile.
 */
final class RolesField
{
    public static function validate(ExecutionContextInterface $context, mixed $roles): void
    {
        if (null === $roles) {
            return;
        }
        if (!\is_array($roles) || !array_is_list($roles)) {
            $context->buildViolation('As roles devem ser uma lista de códigos.')->atPath('roles')->addViolation();

            return;
        }
        foreach ($roles as $code) {
            if (!\is_string($code) || null === Role::tryFrom($code)) {
                $context->buildViolation(sprintf('Role inválida. Valores aceitos: %s.', implode(', ', Role::values())))->atPath('roles')->addViolation();

                return;
            }
        }
    }

    /**
     * Só deve ser chamado depois de a validação passar.
     *
     * @return list<Role>|null
     */
    public static function toRoles(mixed $roles): ?array
    {
        if (null === $roles) {
            return null;
        }

        return array_values(array_unique(array_map(static fn (string $code): Role => Role::from($code), $roles), \SORT_REGULAR));
    }
}
