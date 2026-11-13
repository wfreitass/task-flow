<?php

namespace App\User\Infrastructure\Security;

use App\User\Application\Port\TokenIssuerInterface;
use App\User\Domain\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

/**
 * Emite o JWT com o LexikJWTAuthenticationBundle (chaves, algoritmo e TTL em config/packages/lexik_jwt_authentication.yaml).
 */
final class LexikTokenIssuer implements TokenIssuerInterface
{
    public function __construct(private readonly JWTTokenManagerInterface $jwtManager)
    {
    }

    public function issue(User $user): string
    {
        return $this->jwtManager->create(new SecurityUser($user));
    }
}
