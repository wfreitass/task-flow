<?php

namespace App\Task\Infrastructure\Persistence\Doctrine;

use App\Task\Domain\Entity\TaskComment;
use App\Task\Domain\Repository\TaskCommentRepositoryInterface;
use App\Task\Infrastructure\Persistence\Doctrine\Entity\TaskEntity;
use App\Task\Infrastructure\Persistence\Doctrine\Mapper\TaskCommentMapper;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTaskCommentRepository implements TaskCommentRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TaskCommentMapper $mapper,
    ) {
    }

    public function add(TaskComment $comment): void
    {
        $task = $this->entityManager->getReference(TaskEntity::class, $comment->taskId());
        $entity = $this->mapper->toEntity($comment, $task);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->mapper->syncGeneratedId($comment, $entity);
    }
}
