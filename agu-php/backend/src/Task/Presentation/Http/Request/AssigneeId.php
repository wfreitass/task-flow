<?php

namespace App\Task\Presentation\Http\Request;

/**
 * O `assignedTo` aceita o id do usuário como número (JSON) ou como texto de dígitos (formulário multipart).
 */
final class AssigneeId
{
    public static function isValid(mixed $value): bool
    {
        if (\is_int($value)) {
            return $value > 0;
        }

        return \is_string($value) && 1 === preg_match('/^[1-9]\d{0,9}$/', $value);
    }

    public static function parse(mixed $value): ?int
    {
        return null === $value ? null : (int) $value;
    }
}
