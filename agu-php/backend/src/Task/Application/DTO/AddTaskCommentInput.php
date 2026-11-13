<?php

namespace App\Task\Application\DTO;

final class AddTaskCommentInput
{
    public function __construct(
        public readonly int $taskId,
        public readonly string $content,
    ) {
    }
}
