<?php

namespace App\Shared\Application\Port;

interface MimeTypeDetectorInterface
{
    /**
     * Tipo MIME detectado pelo **conteúdo** do arquivo, não pelo nome nem pelo tipo declarado pelo cliente.
     */
    public function detect(string $path): string;
}
