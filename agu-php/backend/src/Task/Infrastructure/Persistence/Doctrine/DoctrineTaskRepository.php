<?php

namespace App\Task\Infrastructure\Persistence\Doctrine;

use App\Task\Domain\Entity\Task;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Infrastructure\Persistence\Doctrine\Mapper\TaskMapper;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTaskRepository implements TaskRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TaskMapper $mapper,
    ) {
    }

    public function add(Task $task): void
    {
        $entity = $this->mapper->toEntity($task);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->mapper->syncGeneratedId($task, $entity);
    }
}
