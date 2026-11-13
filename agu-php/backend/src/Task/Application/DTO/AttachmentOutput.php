<?php

namespace App\Task\Application\DTO;

use App\Task\Domain\Entity\TaskAttachment;

final class AttachmentOutput
{
    public function __construct(
        public readonly int $id,
        public readonly string $originalName,
        public readonly string $mimeType,
        public readonly int $sizeBytes,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromAttachment(TaskAttachment $attachment): self
    {
        return new self(
            (int) $attachment->id(),
            $attachment->originalName(),
            $attachment->mimeType(),
            $attachment->sizeBytes(),
            $attachment->createdAt(),
        );
    }
}
