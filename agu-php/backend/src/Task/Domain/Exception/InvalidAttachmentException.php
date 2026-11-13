<?php

namespace App\Task\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidAttachmentException extends DomainException
{
    public static function empty(string $name): self
    {
        return new self('ATTACHMENT_EMPTY', sprintf('O arquivo "%s" está vazio.', $name), ['file' => $name]);
    }

    public static function tooLarge(string $name, int $maxBytes): self
    {
        return new self(
            'ATTACHMENT_TOO_LARGE',
            sprintf('O arquivo "%s" excede o tamanho máximo de %d MB.', $name, intdiv($maxBytes, 1024 * 1024)),
            ['file' => $name, 'maxBytes' => $maxBytes],
        );
    }

    /**
     * @param list<string> $allowed
     */
    public static function typeNotAllowed(string $name, string $mimeType, array $allowed): self
    {
        return new self(
            'ATTACHMENT_TYPE_NOT_ALLOWED',
            sprintf('O tipo de arquivo "%s" não é permitido para "%s".', $mimeType, $name),
            ['file' => $name, 'detected' => $mimeType, 'allowed' => $allowed],
        );
    }

    public static function tooMany(int $max): self
    {
        return new self('TOO_MANY_ATTACHMENTS', sprintf('Envie no máximo %d arquivos por vez.', $max), ['max' => $max]);
    }
}
