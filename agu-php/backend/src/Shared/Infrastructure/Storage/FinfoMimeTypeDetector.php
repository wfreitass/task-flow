<?php

namespace App\Shared\Infrastructure\Storage;

use App\Shared\Application\Port\MimeTypeDetectorInterface;

final class FinfoMimeTypeDetector implements MimeTypeDetectorInterface
{
    public function detect(string $path): string
    {
        $mimeType = (new \finfo(\FILEINFO_MIME_TYPE))->file($path);

        return false === $mimeType ? 'application/octet-stream' : $mimeType;
    }
}
