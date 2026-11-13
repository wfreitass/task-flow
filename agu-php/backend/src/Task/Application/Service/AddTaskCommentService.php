<?php

namespace App\Task\Application\Service;

use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\TransactionRunnerInterface;
use App\Task\Application\DTO\AddTaskCommentInput;
use App\Task\Application\DTO\CommentOutput;
use App\Task\Domain\Entity\TaskComment;
use App\Task\Domain\Entity\TaskHistory;
use App\Task\Domain\Enum\TaskHistoryAction;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskCommentRepositoryInterface;
use App\Task\Domain\Repository\TaskHistoryRepositoryInterface;
use App\Task\Domain\Repository\TaskRepositoryInterface;

final class AddTaskCommentService
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskCommentRepositoryInterface $comments,
        private readonly TaskHistoryRepositoryInterface $history,
        private readonly ClockInterface $clock,
        private readonly TransactionRunnerInterface $transactions,
    ) {
    }

    /**
     * Comenta uma tarefa do usuário autenticado. Tarefa alheia, excluída ou inexistente responde "não encontrada".
     */
    public function execute(AddTaskCommentInput $input, int $authenticatedUserId): CommentOutput
    {
        return $this->transactions->run(function () use ($input, $authenticatedUserId): CommentOutput {
            $task = $this->tasks->findOwnedById($input->taskId, $authenticatedUserId) ?? throw new TaskNotFoundException();
            $now = $this->clock->now();

            $comment = TaskComment::create((int) $task->id(), $authenticatedUserId, $input->content, $now);
            $this->comments->add($comment);
            $this->history->add(TaskHistory::record(
                $task,
                $authenticatedUserId,
                TaskHistoryAction::CommentAdded,
                null,
                ['comment_id' => (string) $comment->id()],
                $now,
            ));

            return CommentOutput::fromComment($comment);
        });
    }
}
