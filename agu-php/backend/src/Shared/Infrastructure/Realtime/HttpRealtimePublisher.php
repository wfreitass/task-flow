<?php

namespace App\Shared\Infrastructure\Realtime;

use App\Shared\Application\DTO\RealtimeMessage;
use App\Shared\Application\Port\RealtimePublisherInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Implementa a porta RealtimePublisherInterface entregando a mensagem ao servidor de WebSocket por HTTP, na rede
 * interna (endpoint /push do comando app:realtime:serve), autenticado por um segredo compartilhado.
 *
 * Seguro repetir: o servidor descarta uma mensagem cujo id já entregou. Por isso, na dúvida (ex.: tempo esgotado
 * sem resposta), o Messenger pode tentar de novo sem risco de avisar duas vezes.
 */
final class HttpRealtimePublisher implements RealtimePublisherInterface
{
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly string $pushUrl,
        private readonly string $pushToken,
    ) {
    }

    public function publish(RealtimeMessage $message): void
    {
        try {
            $response = $this->http->request('POST', $this->pushUrl, [
                'headers' => ['X-Realtime-Token' => $this->pushToken],
                'json' => [
                    'id' => $message->id,
                    'type' => $message->type,
                    'occurredAt' => $message->occurredAt,
                    'recipientIds' => $message->recipientIds,
                    'data' => $message->data,
                ],
                'timeout' => 3,
            ]);
            $status = $response->getStatusCode();
        } catch (ExceptionInterface $e) {
            throw new \RuntimeException('Servidor de tempo real indisponível: '.$e->getMessage(), 0, $e);
        }

        if ($status >= 300) {
            throw new \RuntimeException(sprintf('O servidor de tempo real recusou a mensagem (HTTP %d).', $status));
        }
    }
}
