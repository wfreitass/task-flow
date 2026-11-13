<?php

namespace App\Shared\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Guarda ações (como invalidar cache) para rodarem só depois que a transação do banco for confirmada.
 *
 * Por que não invalidar na hora da escrita: entre a invalidação e o commit, outra requisição poderia ler o dado
 * antigo do banco e repopular o cache, que ficaria velho até expirar. Invalidando depois do commit, quem repopular
 * já enxerga o dado novo. Se a transação falhar, nada mudou no banco e as ações são descartadas.
 *
 * Fora de uma transação a ação roda imediatamente.
 */
final class AfterCommitActions
{
    /** @var list<callable> */
    private array $queue = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function execute(callable $action): void
    {
        if ($this->connection->isTransactionActive()) {
            $this->queue[] = $action;

            return;
        }

        $this->run($action);
    }

    /**
     * Roda as ações pendentes. Chamado pelo TransactionRunner depois do commit (e só se a transação terminou).
     */
    public function flush(): void
    {
        if ($this->connection->isTransactionActive()) {
            return;
        }

        $actions = $this->queue;
        $this->queue = [];
        foreach ($actions as $action) {
            $this->run($action);
        }
    }

    /**
     * Descarta as ações pendentes (a transação falhou e foi desfeita).
     */
    public function discard(): void
    {
        $this->queue = [];
    }

    private function run(callable $action): void
    {
        try {
            $action();
        } catch (\Throwable $exception) {
            // A escrita já foi confirmada: uma falha aqui (ex.: cache indisponível) não pode virar erro para o
            // usuário. O prazo de validade do cache limita por quanto tempo um dado velho pode aparecer.
            $this->logger->warning('Ação pós-commit falhou: {message}', ['message' => $exception->getMessage(), 'exception' => $exception]);
        }
    }
}
