<?php

namespace App\Task\Infrastructure\Persistence\Doctrine;

use App\Task\Domain\Entity\TaskHistory;
use App\Task\Domain\Repository\TaskHistoryRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTaskHistoryRepository implements TaskHistoryRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function add(TaskHistory $entry): void
    {
        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }
}
