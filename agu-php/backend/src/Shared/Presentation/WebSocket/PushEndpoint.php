<?php

namespace App\Shared\Presentation\WebSocket;

use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

/**
 * Endpoint HTTP interno (POST /push) pelo qual a aplicação entrega mensagens ao servidor de WebSocket. Só existe
 * na rede interna do Docker (o Nginx não o expõe) e exige o segredo compartilhado no cabeçalho X-Realtime-Token.
 * GET /health responde 200, para o healthcheck.
 */
final class PushEndpoint
{
    public function __construct(
        private readonly RealtimeServer $server,
        private readonly string $token,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): Response
    {
        $path = $request->getUri()->getPath();
        if ('/health' === $path) {
            return self::json(200, ['status' => 'ok', 'connections' => $this->server->connectionCount()]);
        }
        if ('/push' !== $path || 'POST' !== $request->getMethod()) {
            return self::json(404, ['error' => 'not_found']);
        }
        if ('' === $this->token || !hash_equals($this->token, $request->getHeaderLine('X-Realtime-Token'))) {
            return self::json(401, ['error' => 'unauthorized']);
        }

        $body = json_decode((string) $request->getBody(), true);
        if (!is_array($body)
            || !is_string($body['id'] ?? null) || '' === $body['id']
            || !is_string($body['type'] ?? null) || '' === $body['type']
            || !is_string($body['occurredAt'] ?? null)
            || !is_array($body['recipientIds'] ?? null)
            || !is_array($body['data'] ?? null)
        ) {
            return self::json(422, ['error' => 'invalid_message']);
        }
        $recipients = array_values(array_filter($body['recipientIds'], 'is_int'));

        $result = $this->server->deliver($body['id'], $body['type'], $body['occurredAt'], $recipients, $body['data']);

        // 202 também para repetição: para quem publica, "já entregue" é sucesso.
        return self::json(202, $result);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function json(int $status, array $payload): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($payload));
    }
}
