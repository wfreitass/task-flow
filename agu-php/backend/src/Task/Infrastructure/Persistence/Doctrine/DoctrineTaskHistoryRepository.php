<?php

namespace App\Task\Infrastructure\Persistence\Doctrine;

use App\Task\Domain\Entity\TaskHistory;
use App\Task\Domain\Repository\TaskHistoryRepositoryInterface;
use App\Task\Infrastructure\Persistence\Doctrine\Entity\TaskEntity;
use App\Task\Infrastructure\Persistence\Doctrine\Mapper\TaskHistoryMapper;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTaskHistoryRepository implements TaskHistoryRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TaskHistoryMapper $mapper,
    ) {
    }

    public function add(TaskHistory $entry): void
    {
        // A tarefa já foi gravada (tem id): usa a referência gerenciada pelo Doctrine, sem nova consulta.
        $task = $this->entityManager->getReference(TaskEntity::class, (int) $entry->task()->id());

        $this->entityManager->persist($this->mapper->toEntity($entry, $task));
        $this->entityManager->flush();
    }
}
