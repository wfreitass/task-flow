<?php

namespace App\Shared\Application\Port;

/**
 * Cache de dados da aplicação, com tags para invalidar por grupo (ex.: tudo de um usuário) sem varrer chaves.
 * Implementada em Shared/Infrastructure/Cache; o backend (Redis, memória) é detalhe da infraestrutura.
 *
 * Os valores devem ser dados simples (arrays, escalares), nunca objetos de domínio: assim o cache continua
 * legível quando uma classe mudar.
 */
interface CacheInterface
{
    /**
     * Devolve o valor guardado em `$key` ou, se não houver, executa `$loader`, guarda o resultado e o devolve.
     * Se o cache estiver indisponível, apenas executa `$loader`: a aplicação segue funcionando, só sem cache.
     *
     * @template T
     *
     * @param list<string>   $tags       grupos a que a entrada pertence, para invalidateTags()
     * @param callable(): T  $loader
     * @param int|null       $ttlSeconds validade em segundos; null usa o padrão do cache
     *
     * @return T
     */
    public function remember(string $key, array $tags, callable $loader, ?int $ttlSeconds = null): mixed;

    /**
     * Invalida todas as entradas de qualquer uma das tags informadas.
     *
     * @param list<string> $tags
     */
    public function invalidateTags(array $tags): void;
}
