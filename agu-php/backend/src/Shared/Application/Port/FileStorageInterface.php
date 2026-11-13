<?php

namespace App\Shared\Application\Port;

use App\Shared\Application\DTO\IncomingFile;
use App\Shared\Application\DTO\StoredFile;

interface FileStorageInterface
{
    /**
     * Move o arquivo recebido para o armazenamento privado e devolve a chave com que ele pode ser encontrado.
     * O nome gravado em disco é gerado aqui; o nome original do cliente nunca é usado como caminho.
     */
    public function store(IncomingFile $file, string $mimeType): StoredFile;

    /**
     * Remove um arquivo já gravado (compensação quando a transação do banco falha). Chave desconhecida é ignorada.
     */
    public function delete(string $storageKey): void;
}
