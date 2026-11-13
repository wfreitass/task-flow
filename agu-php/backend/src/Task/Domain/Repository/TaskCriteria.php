<?php

namespace App\Task\Domain\Repository;

use App\Shared\Domain\SortDirection;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskSortField;
use App\Task\Domain\Enum\TaskStatus;

/**
 * Filtros, ordenação e paginação de uma busca de tarefas. Sempre restrita às tarefas de um dono.
 */
final class TaskCriteria
{
    public const MAX_PER_PAGE = 100;

    public readonly int $page;
    public readonly int $perPage;

    /**
     * @param list<TaskStatus>   $statuses  vazio = qualquer status
     * @param list<TaskPriority> $priorities vazio = qualquer prioridade
     */
    public function __construct(
        public readonly int $ownerId,
        public readonly array $statuses = [],
        public readonly array $priorities = [],
        public readonly ?string $search = null,
        public readonly ?\DateTimeImmutable $dueFrom = null,
        public readonly ?\DateTimeImmutable $dueTo = null,
        public readonly TaskSortField $sortBy = TaskSortField::CreatedAt,
        public readonly SortDirection $direction = SortDirection::Desc,
        int $page = 1,
        int $perPage = 20,
    ) {
        $this->page = max(1, $page);
        $this->perPage = min(self::MAX_PER_PAGE, max(1, $perPage));
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
