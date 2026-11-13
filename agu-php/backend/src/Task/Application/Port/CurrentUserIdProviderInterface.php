<?php

namespace App\Task\Application\Port;

/**
 * Identidade do usuário autenticado. Implementada pelo contexto User (Infrastructure).
 */
interface CurrentUserIdProviderInterface
{
    public function currentUserId(): int;
}
