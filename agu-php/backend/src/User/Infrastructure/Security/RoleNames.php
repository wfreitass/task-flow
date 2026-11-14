<?php

namespace App\User\Infrastructure\Security;

use App\User\Domain\Enum\UserRole;

/**
 * Tradução entre o perfil do domínio (UserRole) e os nomes de papel do Symfony Security, que também são o que
 * fica na coluna `users.roles` (JSON). O domínio não conhece estes nomes.
 *
 * Quem só tem o antigo `ROLE_USER` (cadastrado antes dos perfis) é lido como executor, o perfil mais restrito:
 * não é preciso migrar dados.
 */
final class RoleNames
{
    private const ADMIN = 'ROLE_ADMIN';
    private const EXECUTOR = 'ROLE_EXECUTOR';

    public static function toSecurity(UserRole $role): string
    {
        return UserRole::Admin === $role ? self::ADMIN : self::EXECUTOR;
    }

    /**
     * O que gravar em `users.roles`.
     *
     * @return list<string>
     */
    public static function toStored(UserRole $role): array
    {
        return [self::toSecurity($role)];
    }

    /**
     * @param array<mixed> $stored o conteúdo de `users.roles`
     */
    public static function fromStored(array $stored): UserRole
    {
        return \in_array(self::ADMIN, $stored, true) ? UserRole::Admin : UserRole::Executor;
    }
}
