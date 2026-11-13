<?php

namespace App\Task\Application\DTO;

use App\Task\Domain\Enum\TaskStatus;

final class ChangeTaskStatusInput
{
    public function __construct(
        public readonly int $taskId,
        public readonly TaskStatus $status,
    ) {
    }
}
