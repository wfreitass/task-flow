<?php

namespace App\Shared\Application\Port;

interface TransactionRunnerInterface
{
    /**
     * Executa o trabalho em uma transação: confirma se terminar, desfaz se lançar exceção.
     *
     * @template T
     *
     * @param callable(): T $work
     *
     * @return T
     */
    public function run(callable $work): mixed;
}
