<?php

namespace App\Task\Application\DTO;

use App\Task\Domain\Entity\TaskComment;

final class CommentOutput
{
    public function __construct(
        public readonly int $id,
        public readonly int $taskId,
        public readonly int $authorId,
        public readonly string $content,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromComment(TaskComment $comment): self
    {
        return new self(
            (int) $comment->id(),
            $comment->taskId(),
            $comment->authorId(),
            $comment->content(),
            $comment->createdAt(),
        );
    }
}
