<?php

namespace App\Task\Presentation\Http\Response;

use App\Task\Application\DTO\CommentOutput;

final class CommentResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function fromOutput(CommentOutput $comment): array
    {
        return [
            'id' => $comment->id,
            'taskId' => $comment->taskId,
            'authorId' => $comment->authorId,
            'content' => $comment->content,
            'createdAt' => $comment->createdAt->format(\DATE_ATOM),
        ];
    }
}
