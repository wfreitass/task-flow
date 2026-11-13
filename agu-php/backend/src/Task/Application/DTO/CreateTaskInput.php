<?php

namespace App\Task\Application\DTO;

use App\Task\Domain\Enum\TaskPriority;

final class CreateTaskInput
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly TaskPriority $priority,
        public readonly ?\DateTimeImmutable $dueDate,
    ) {
    }
}
