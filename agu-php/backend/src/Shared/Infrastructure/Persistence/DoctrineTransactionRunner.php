<?php

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Application\Port\TransactionRunnerInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTransactionRunner implements TransactionRunnerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AfterCommitActions $afterCommit,
    ) {
    }

    public function run(callable $work): mixed
    {
        try {
            $result = $this->entityManager->wrapInTransaction($work);
        } catch (\Throwable $exception) {
            $this->afterCommit->discard();

            throw $exception;
        }

        // Só aqui, com a transação confirmada, as ações pendentes (ex.: invalidar cache) podem rodar.
        $this->afterCommit->flush();

        return $result;
    }
}
