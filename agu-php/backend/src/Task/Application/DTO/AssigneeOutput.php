<?php

namespace App\Task\Application\DTO;

final class AssigneeOutput
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
    ) {
    }
}
