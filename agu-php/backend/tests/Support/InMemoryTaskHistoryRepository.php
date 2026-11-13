<?php

namespace App\Tests\Support;

use App\Task\Domain\Entity\TaskHistory;
use App\Task\Domain\Repository\TaskHistoryRepositoryInterface;

final class InMemoryTaskHistoryRepository implements TaskHistoryRepositoryInterface
{
    /** @var list<TaskHistory> */
    public array $entries = [];

    public function add(TaskHistory $entry): void
    {
        $this->entries[] = $entry;
    }
}
