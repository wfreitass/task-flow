<?php

namespace App\Shared\Application\DTO;

/**
 * Arquivo recebido pela API, ainda no diretório temporário do PHP. É a visão da aplicação sobre o
 * objeto UploadedFile do framework HTTP, que não deve vazar para fora da camada de apresentação.
 */
final class IncomingFile
{
    public function __construct(
        public readonly string $tempPath,
        public readonly string $originalName,
        public readonly int $size,
    ) {
    }
}
