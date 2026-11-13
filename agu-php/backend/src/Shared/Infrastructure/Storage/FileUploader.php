<?php

namespace App\Shared\Infrastructure\Storage;

use App\Shared\Application\DTO\IncomingFile;
use App\Shared\Application\DTO\StoredFile;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\FileStorageInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Serviço de upload (padrão da documentação "How to Upload Files" do Symfony): move o arquivo para um diretório
 * privado, injetado por parâmetro, e devolve o nome gravado. Diferenças deliberadas:
 *  - o nome em disco é aleatório (128 bits) + extensão pelo tipo detectado, nunca derivado do nome do cliente;
 *  - a organização é por ano/mês, para não acumular milhares de arquivos numa só pasta;
 *  - calcula o SHA-256, que a tabela `task_attachments` guarda.
 * O diretório fica fora de `public/`: arquivos só saem por um endpoint que valide o acesso.
 */
final class FileUploader implements FileStorageInterface
{
    private const EXTENSIONS = [
        'application/pdf' => 'pdf',
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'text/plain' => 'txt',
        'text/csv' => 'csv',
    ];

    /** Formato exato das chaves geradas aqui; qualquer outra é recusada em delete(). */
    private const KEY_PATTERN = '#^\d{4}/\d{2}/[a-f0-9]{32}\.[a-z0-9]{1,5}$#';

    public function __construct(
        private readonly string $targetDirectory,
        private readonly ClockInterface $clock,
    ) {
    }

    public function store(IncomingFile $file, string $mimeType): StoredFile
    {
        $checksum = @hash_file('sha256', $file->tempPath);
        if (false === $checksum) {
            throw new \RuntimeException('Não foi possível ler o arquivo enviado.');
        }

        $subdirectory = $this->clock->now()->format('Y/m');
        $fileName = bin2hex(random_bytes(16)).'.'.(self::EXTENSIONS[$mimeType] ?? 'bin');

        try {
            (new File($file->tempPath))->move($this->targetDirectory.'/'.$subdirectory, $fileName);
        } catch (FileException $exception) {
            throw new \RuntimeException('Não foi possível gravar o arquivo enviado.', 0, $exception);
        }

        return new StoredFile($subdirectory.'/'.$fileName, $checksum);
    }

    public function delete(string $storageKey): void
    {
        // Só apaga chaves no formato gerado por store(): impede que uma chave adulterada saia do diretório.
        if (1 !== preg_match(self::KEY_PATTERN, $storageKey)) {
            return;
        }

        $path = $this->targetDirectory.'/'.$storageKey;
        if (is_file($path)) {
            unlink($path);
        }
    }
}
