<?php

namespace App\Task\Application\DTO;

use App\Shared\Application\DTO\IncomingFile;
use App\Task\Domain\Enum\TaskPriority;

final class CreateTaskInput
{
    /**
     * @param list<IncomingFile> $attachments arquivos enviados junto com a tarefa
     */
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly TaskPriority $priority,
        public readonly ?\DateTimeImmutable $startDate,
        public readonly ?\DateTimeImmutable $dueDate,
        public readonly array $attachments = [],
    ) {
    }
}
