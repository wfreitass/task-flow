<?php

namespace App\Task\Infrastructure\Persistence\Doctrine;

use App\Task\Application\DTO\ActivityRecord;
use App\Task\Application\Port\TaskActivityReaderInterface;
use App\Task\Infrastructure\Persistence\Doctrine\Entity\TaskCommentEntity;
use App\Task\Infrastructure\Persistence\Doctrine\Entity\TaskHistoryEntity;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTaskActivityReader implements TaskActivityReaderInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    public function comments(int $taskId): array
    {
        $entities = $this->entityManager->getRepository(TaskCommentEntity::class)->findBy(
            ['task' => $taskId, 'deletedAt' => null], ['createdAt' => 'ASC', 'id' => 'ASC'],
        );
        return array_map(static fn (TaskCommentEntity $item): ActivityRecord => new ActivityRecord(
            (int) $item->id, $item->authorId, 'comment', $item->createdAt, $item->content,
        ), $entities);
    }

    public function history(int $taskId): array
    {
        $entities = $this->entityManager->getRepository(TaskHistoryEntity::class)->findBy(
            ['task' => $taskId], ['createdAt' => 'DESC', 'id' => 'DESC'],
        );
        return array_map(static fn (TaskHistoryEntity $item): ActivityRecord => new ActivityRecord(
            (int) $item->id, $item->actorId, $item->action->value, $item->createdAt, null, $item->oldValue, $item->newValue,
        ), $entities);
    }
}
