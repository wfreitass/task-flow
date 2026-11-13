<?php

namespace App\Shared\Application\DTO;

final class StoredFile
{
    public function __construct(
        public readonly string $storageKey,
        public readonly string $checksumSha256,
    ) {
    }
}
