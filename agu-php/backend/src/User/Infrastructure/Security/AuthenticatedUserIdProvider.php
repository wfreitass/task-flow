<?php

namespace App\User\Infrastructure\Security;

use App\Shared\Application\Port\CurrentUserIdProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

/**
 * Implementa a porta do contexto Task usando o usuário autenticado pelo Symfony Security.
 */
final class AuthenticatedUserIdProvider implements CurrentUserIdProviderInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function currentUserId(): int
    {
        $user = $this->security->getUser();
        if (!$user instanceof SecurityUser) {
            throw new AuthenticationCredentialsNotFoundException();
        }

        return $user->id();
    }
}
