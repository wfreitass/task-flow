<?php

namespace App\Task\Domain\Entity;

use App\Task\Domain\Exception\InvalidCommentException;

final class TaskComment
{
    public const MAX_LENGTH = 5000;

    private ?int $id = null;

    private function __construct(
        private int $taskId,
        private int $authorId,
        private string $content,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
        private ?\DateTimeImmutable $deletedAt,
    ) {
    }

    public static function create(int $taskId, int $authorId, string $content, \DateTimeImmutable $now): self
    {
        $content = trim($content);
        if ('' === $content) {
            throw InvalidCommentException::contentRequired();
        }
        if (mb_strlen($content) > self::MAX_LENGTH) {
            throw InvalidCommentException::contentTooLong(self::MAX_LENGTH);
        }

        return new self($taskId, $authorId, $content, $now, $now, null);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function taskId(): int
    {
        return $this->taskId;
    }

    public function authorId(): int
    {
        return $this->authorId;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
