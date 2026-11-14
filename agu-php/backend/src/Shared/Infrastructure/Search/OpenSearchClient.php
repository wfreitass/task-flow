<?php

namespace App\Shared\Infrastructure\Search;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Cliente mínimo da API REST do OpenSearch (JSON sobre HTTP). Sem biblioteca própria: as poucas chamadas que a
 * aplicação faz (indexar, apagar, buscar, criar índice) cabem aqui. Qualquer falha vira OpenSearchException.
 */
final class OpenSearchClient
{
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly string $baseUrl,
    ) {
    }

    /**
     * @param array<string, mixed>|null $body
     * @param list<int>                 $acceptedStatuses status HTTP, além dos 2xx, que não são erro (ex.: 404)
     *
     * @return array{status: int, body: array<string, mixed>}
     *
     * @throws OpenSearchException
     */
    public function request(string $method, string $path, ?array $body = null, array $acceptedStatuses = []): array
    {
        return $this->send($method, $path, null === $body ? [] : ['json' => $body], $acceptedStatuses);
    }

    /**
     * Operações em lote (_bulk): o corpo é NDJSON, uma linha de JSON por linha.
     *
     * @param list<array<string, mixed>> $lines
     *
     * @return array{status: int, body: array<string, mixed>}
     *
     * @throws OpenSearchException
     */
    public function bulk(string $path, array $lines): array
    {
        $ndjson = implode("\n", array_map(static fn (array $line): string => json_encode($line, \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR), $lines))."\n";

        return $this->send('POST', $path, ['body' => $ndjson, 'headers' => ['Content-Type' => 'application/x-ndjson']], []);
    }

    /**
     * @param array<string, mixed> $options
     * @param list<int>            $acceptedStatuses
     *
     * @return array{status: int, body: array<string, mixed>}
     */
    private function send(string $method, string $path, array $options, array $acceptedStatuses): array
    {
        try {
            $response = $this->http->request($method, rtrim($this->baseUrl, '/').'/'.ltrim($path, '/'), $options + ['timeout' => 5]);
            $status = $response->getStatusCode();
            $body = json_decode($response->getContent(false), true);
        } catch (ExceptionInterface $e) {
            throw new OpenSearchException('OpenSearch indisponível: '.$e->getMessage(), 0, $e);
        }

        if (($status < 200 || $status >= 300) && !\in_array($status, $acceptedStatuses, true)) {
            $reason = is_array($body) ? ($body['error']['type'] ?? json_encode($body['error'] ?? $body)) : 'resposta inesperada';
            throw new OpenSearchException(sprintf('OpenSearch respondeu HTTP %d (%s) em %s %s.', $status, $reason, $method, $path));
        }

        return ['status' => $status, 'body' => is_array($body) ? $body : []];
    }
}
