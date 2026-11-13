<?php

namespace App\Shared\Application\Port;

/**
 * Identidade do usuário autenticado na requisição atual. Usada por mais de um contexto;
 * implementada pelo contexto User (Infrastructure), a partir do token validado.
 */
interface CurrentUserIdProviderInterface
{
    public function currentUserId(): int;
}
