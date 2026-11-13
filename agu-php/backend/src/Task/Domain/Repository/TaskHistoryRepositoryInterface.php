<?php

namespace App\Task\Domain\Repository;

use App\Task\Domain\Entity\TaskHistory;

/**
 * O histórico é somente de inserção: não há operações de alteração ou remoção.
 */
interface TaskHistoryRepositoryInterface
{
    public function add(TaskHistory $entry): void;
}
