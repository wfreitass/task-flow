<?php

namespace App\Shared\Presentation\WebSocket;

/**
 * Memória curta dos ids de mensagens já entregues: é o que torna a entrega idempotente. Guarda cada id por um
 * tempo e no máximo um número de ids (o mais antigo sai primeiro), para não crescer sem limite num processo que
 * fica dias no ar. Se o servidor reiniciar, a memória zera; o cliente ainda descarta repetições pelo id e por
 * `occurredAt`.
 */
final class RecentIds
{
    /** @var array<string, int> id => instante (segundos) em que foi visto; a ordem é a de chegada */
    private array $seen = [];

    public function __construct(
        private readonly int $ttlSeconds = 600,
        private readonly int $maxEntries = 5000,
    ) {
    }

    public function has(string $id, int $now): bool
    {
        $this->expire($now);

        return isset($this->seen[$id]);
    }

    public function remember(string $id, int $now): void
    {
        unset($this->seen[$id]);
        $this->seen[$id] = $now;
        while (count($this->seen) > $this->maxEntries) {
            unset($this->seen[array_key_first($this->seen)]);
        }
    }

    private function expire(int $now): void
    {
        foreach ($this->seen as $id => $seenAt) {
            if ($now - $seenAt < $this->ttlSeconds) {
                break;
            }
            unset($this->seen[$id]);
        }
    }
}
