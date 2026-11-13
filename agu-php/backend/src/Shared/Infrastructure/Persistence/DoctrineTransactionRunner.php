<?php

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Application\Port\TransactionRunnerInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTransactionRunner implements TransactionRunnerInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function run(callable $work): mixed
    {
        return $this->entityManager->wrapInTransaction($work);
    }
}
