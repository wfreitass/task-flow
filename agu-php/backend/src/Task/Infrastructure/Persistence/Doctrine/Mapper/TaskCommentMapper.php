<?php

namespace App\Task\Infrastructure\Persistence\Doctrine\Mapper;

use App\Shared\Infrastructure\Persistence\DomainObjectHydrator;
use App\Task\Domain\Entity\TaskComment;
use App\Task\Infrastructure\Persistence\Doctrine\Entity\TaskCommentEntity;
use App\Task\Infrastructure\Persistence\Doctrine\Entity\TaskEntity;

final class TaskCommentMapper
{
    public function __construct(private readonly DomainObjectHydrator $hydrator)
    {
    }

    public function toEntity(TaskComment $comment, TaskEntity $task): TaskCommentEntity
    {
        $entity = new TaskCommentEntity();
        $entity->task = $task;
        $entity->authorId = $comment->authorId();
        $entity->content = $comment->content();
        $entity->createdAt = $comment->createdAt();
        $entity->updatedAt = $comment->updatedAt();
        $entity->deletedAt = $comment->deletedAt();

        return $entity;
    }

    public function syncGeneratedId(TaskComment $comment, TaskCommentEntity $entity): void
    {
        $this->hydrator->set($comment, 'id', $entity->id);
    }
}
