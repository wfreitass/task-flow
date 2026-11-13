<?php

namespace App\Task\Infrastructure\Persistence\Doctrine\Mapper;

use App\Shared\Infrastructure\Persistence\DomainObjectHydrator;
use App\Task\Domain\Entity\TaskAttachment;
use App\Task\Infrastructure\Persistence\Doctrine\Entity\TaskAttachmentEntity;
use App\Task\Infrastructure\Persistence\Doctrine\Entity\TaskEntity;

final class TaskAttachmentMapper
{
    public function __construct(private readonly DomainObjectHydrator $hydrator)
    {
    }

    public function toEntity(TaskAttachment $attachment, TaskEntity $task): TaskAttachmentEntity
    {
        $entity = new TaskAttachmentEntity();
        $entity->task = $task;
        $entity->uploadedBy = $attachment->uploadedBy();
        $entity->originalName = $attachment->originalName();
        $entity->storageKey = $attachment->storageKey();
        $entity->mimeType = $attachment->mimeType();
        $entity->sizeBytes = $attachment->sizeBytes();
        $entity->checksumSha256 = $attachment->checksumSha256();
        $entity->createdAt = $attachment->createdAt();
        $entity->deletedAt = $attachment->deletedAt();

        return $entity;
    }

    public function syncGeneratedId(TaskAttachment $attachment, TaskAttachmentEntity $entity): void
    {
        $this->hydrator->set($attachment, 'id', $entity->id);
    }
}
