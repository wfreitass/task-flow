<?php

namespace App\Task\Application\Service;

use App\Task\Application\DTO\ActivityRecord;
use App\Task\Application\Port\TaskActivityReaderInterface;
use App\Task\Application\Port\UserDirectoryInterface;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;

final class ListTaskActivityService
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskActivityReaderInterface $activity,
        private readonly UserDirectoryInterface $users,
    ) {}

    /** @return list<array<string, mixed>> */
    public function execute(int $taskId, int $userId, bool $history): array
    {
        $this->tasks->findAccessibleById($taskId, $userId) ?? throw new TaskNotFoundException();
        $records = $history ? $this->activity->history($taskId) : $this->activity->comments($taskId);
        $names = $this->users->findByIds(array_values(array_unique(array_map(static fn (ActivityRecord $item): int => $item->actorId, $records))));
        return array_map(static fn (ActivityRecord $item): array => [
            'id' => $item->id, 'actorId' => $item->actorId,
            'actorName' => $names[$item->actorId]->username ?? null,
            'kind' => $item->kind, 'content' => $item->content,
            'oldValue' => $item->oldValue, 'newValue' => $item->newValue,
            'createdAt' => $item->createdAt->format('Y-m-d\TH:i:s.u\Z'),
        ], $records);
    }
}
