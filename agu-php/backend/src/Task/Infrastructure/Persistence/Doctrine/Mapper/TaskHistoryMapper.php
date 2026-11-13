<?php

namespace App\Task\Infrastructure\Persistence\Doctrine\Mapper;

use App\Task\Domain\Entity\TaskHistory;
use App\Task\Infrastructure\Persistence\Doctrine\Entity\TaskEntity;
use App\Task\Infrastructure\Persistence\Doctrine\Entity\TaskHistoryEntity;

/**
 * Converte o registro de histórico do domínio no modelo de persistência. Só há escrita: o histórico é append-only.
 */
final class TaskHistoryMapper
{
    public function toEntity(TaskHistory $history, TaskEntity $task): TaskHistoryEntity
    {
        $entity = new TaskHistoryEntity();
        $entity->task = $task;
        $entity->actorId = $history->actorId();
        $entity->action = $history->action();
        $entity->oldValue = $history->oldValue();
        $entity->newValue = $history->newValue();
        $entity->createdAt = $history->createdAt();

        return $entity;
    }
}
