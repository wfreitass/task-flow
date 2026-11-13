<?php

namespace App\Task\Infrastructure\Persistence\Doctrine;

use App\Task\Domain\Entity\Task;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTaskRepository implements TaskRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function add(Task $task): void
    {
        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }
}
