<?php

namespace App\Task\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidCommentException extends DomainException
{
    public static function contentRequired(): self
    {
        return new self('COMMENT_CONTENT_REQUIRED', 'O comentário não pode ficar em branco.', ['content' => 'required']);
    }

    public static function contentTooLong(int $max): self
    {
        return new self('COMMENT_CONTENT_TOO_LONG', sprintf('O comentário deve ter no máximo %d caracteres.', $max), ['content' => 'too_long']);
    }
}
