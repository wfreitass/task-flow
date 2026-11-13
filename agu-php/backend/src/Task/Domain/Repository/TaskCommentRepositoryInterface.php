<?php

namespace App\Task\Domain\Repository;

use App\Task\Domain\Entity\TaskComment;

interface TaskCommentRepositoryInterface
{
    /**
     * Persiste um novo comentário. Após a chamada, o comentário tem id.
     */
    public function add(TaskComment $comment): void;
}
