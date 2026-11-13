<?php

namespace App\User\Application\Port;

use App\User\Domain\Entity\User;

interface TokenIssuerInterface
{
    /**
     * Emite o token de acesso (JWT) do usuário.
     */
    public function issue(User $user): string;
}
