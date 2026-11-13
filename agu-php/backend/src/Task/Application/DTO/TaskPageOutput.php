<?php

namespace App\Task\Application\DTO;

final class TaskPageOutput
{
    /**
     * @param list<TaskOutput> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {
    }

    public function totalPages(): int
    {
        return (int) max(1, ceil($this->total / $this->perPage));
    }
}
