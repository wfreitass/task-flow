<?php

namespace App\Shared\Application\DTO;

/**
 * Quem um token de acesso válido representa, e até quando ele vale.
 */
final class AccessIdentity
{
    public function __construct(
        public readonly int $userId,
        public readonly \DateTimeImmutable $expiresAt,
    ) {
    }
}
