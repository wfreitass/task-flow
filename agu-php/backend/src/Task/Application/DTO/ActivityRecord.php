<?php

namespace App\Task\Application\DTO;

final class ActivityRecord
{
    /** @param array<string, mixed>|null $oldValue @param array<string, mixed>|null $newValue */
    public function __construct(
        public readonly int $id,
        public readonly int $actorId,
        public readonly string $kind,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?string $content = null,
        public readonly ?array $oldValue = null,
        public readonly ?array $newValue = null,
    ) {
    }
}
