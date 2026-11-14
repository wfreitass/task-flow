<?php

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidProfileException extends DomainException
{
    public static function invalidCode(): self
    {
        return new self('INVALID_PROFILE_CODE', 'O código do perfil deve ter de 2 a 50 caracteres: letra minúscula no início e depois letras minúsculas, números, "_" ou "-".');
    }

    public static function invalidName(): self
    {
        return new self('INVALID_PROFILE_NAME', 'O nome do perfil é obrigatório e deve ter no máximo 100 caracteres.');
    }

    public static function descriptionTooLong(): self
    {
        return new self('INVALID_PROFILE_DESCRIPTION', 'A descrição do perfil deve ter no máximo 255 caracteres.');
    }
}
