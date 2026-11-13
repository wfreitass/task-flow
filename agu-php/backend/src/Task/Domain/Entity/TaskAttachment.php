<?php

namespace App\Task\Domain\Entity;

use App\Task\Domain\Exception\InvalidAttachmentException;

/**
 * Metadados de um arquivo anexado a uma tarefa. O conteúdo fica no armazenamento privado,
 * localizado por `storageKey`; aqui só vivem as regras sobre o que pode ser anexado.
 */
final class TaskAttachment
{
    public const MAX_SIZE_BYTES = 10 * 1024 * 1024;
    public const MAX_PER_UPLOAD = 5;

    /** Tipos aceitos, conferidos pelo conteúdo do arquivo (não pelo nome nem pelo tipo declarado). */
    public const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
        'text/plain',
        'text/csv',
    ];

    private ?int $id = null;

    private function __construct(
        private int $taskId,
        private int $uploadedBy,
        private string $originalName,
        private string $storageKey,
        private string $mimeType,
        private int $sizeBytes,
        private ?string $checksumSha256,
        private \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $deletedAt,
    ) {
    }

    /**
     * Confere o que dá para conferir antes de gravar o arquivo em disco.
     *
     * @throws InvalidAttachmentException
     */
    public static function assertAcceptable(string $originalName, string $mimeType, int $sizeBytes): void
    {
        $name = self::normalizeName($originalName);

        if ($sizeBytes <= 0) {
            throw InvalidAttachmentException::empty($name);
        }
        if ($sizeBytes > self::MAX_SIZE_BYTES) {
            throw InvalidAttachmentException::tooLarge($name, self::MAX_SIZE_BYTES);
        }
        if (!\in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw InvalidAttachmentException::typeNotAllowed($name, $mimeType, self::ALLOWED_MIME_TYPES);
        }
    }

    /**
     * @throws InvalidAttachmentException
     */
    public static function assertCount(int $count): void
    {
        if ($count > self::MAX_PER_UPLOAD) {
            throw InvalidAttachmentException::tooMany(self::MAX_PER_UPLOAD);
        }
    }

    public static function create(
        int $taskId,
        int $uploadedBy,
        string $originalName,
        string $storageKey,
        string $mimeType,
        int $sizeBytes,
        ?string $checksumSha256,
        \DateTimeImmutable $now,
    ): self {
        self::assertAcceptable($originalName, $mimeType, $sizeBytes);

        return new self($taskId, $uploadedBy, self::normalizeName($originalName), $storageKey, $mimeType, $sizeBytes, $checksumSha256, $now, null);
    }

    /**
     * O nome do cliente só serve para exibição: remove caminhos e caracteres de controle. Nunca vira caminho em disco.
     */
    private static function normalizeName(string $originalName): string
    {
        $name = basename(str_replace('\\', '/', $originalName));
        $name = trim(preg_replace('/[\x00-\x1F\x7F]+/u', '', $name) ?? '');

        return '' === $name ? 'arquivo' : mb_substr($name, 0, 255);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function taskId(): int
    {
        return $this->taskId;
    }

    public function uploadedBy(): int
    {
        return $this->uploadedBy;
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function storageKey(): string
    {
        return $this->storageKey;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function sizeBytes(): int
    {
        return $this->sizeBytes;
    }

    public function checksumSha256(): ?string
    {
        return $this->checksumSha256;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function deletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
