<?php

namespace App\Shared\Infrastructure\Cache;

use App\Shared\Application\Port\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Implementa a porta CacheInterface com um pool do Symfony Cache que aceita tags (`cache.app_data`, definido em
 * config/packages/cache.yaml): Redis no desenvolvimento e na produção, memória nos testes.
 *
 * Falhas do backend (por exemplo, Redis fora do ar) não chegam ao chamador: o Symfony Cache as trata como
 * "sem cache" e registra no log, então `remember()` apenas executa o carregador.
 */
final class TaggedPoolCache implements CacheInterface
{
    public function __construct(private readonly TagAwareCacheInterface $pool)
    {
    }

    public function remember(string $key, array $tags, callable $loader, ?int $ttlSeconds = null): mixed
    {
        return $this->pool->get($key, static function (ItemInterface $item) use ($tags, $loader, $ttlSeconds): mixed {
            $item->tag($tags);
            if (null !== $ttlSeconds) {
                $item->expiresAfter($ttlSeconds);
            }

            return $loader();
        });
    }

    public function invalidateTags(array $tags): void
    {
        $this->pool->invalidateTags($tags);
    }
}
