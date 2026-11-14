<?php

namespace App\Task\Application\Port;

use App\Task\Application\DTO\ActivityRecord;

interface TaskActivityReaderInterface
{
    /** @return list<ActivityRecord> */
    public function comments(int $taskId): array;

    /** @return list<ActivityRecord> */
    public function history(int $taskId): array;
}
