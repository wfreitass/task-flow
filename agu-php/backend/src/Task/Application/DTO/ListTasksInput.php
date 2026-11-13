<?php

namespace App\Task\Application\DTO;

use App\Shared\Domain\SortDirection;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskScope;
use App\Task\Domain\Enum\TaskSortField;
use App\Task\Domain\Enum\TaskStatus;

final class ListTasksInput
{
    /**
     * @param list<TaskStatus>   $statuses
     * @param list<TaskPriority> $priorities
     */
    public function __construct(
        public readonly array $statuses = [],
        public readonly array $priorities = [],
        public readonly ?string $search = null,
        public readonly ?\DateTimeImmutable $dueFrom = null,
        public readonly ?\DateTimeImmutable $dueTo = null,
        public readonly TaskSortField $sortBy = TaskSortField::CreatedAt,
        public readonly SortDirection $direction = SortDirection::Desc,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly TaskScope $scope = TaskScope::All,
    ) {
    }
}
