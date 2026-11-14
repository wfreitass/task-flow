<?php

namespace App\Shared\Application\Port;

use App\Shared\Application\DTO\AccessIdentity;

/**
 * Valida um token de acesso (o mesmo JWT da API) fora de uma requisição HTTP, por exemplo no WebSocket.
 */
interface AccessTokenVerifierInterface
{
    /**
     * @return AccessIdentity|null null se o token for inválido, estiver expirado ou o usuário não puder mais acessar
     */
    public function verify(string $token): ?AccessIdentity;
}
