<?php

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

final class ProfileCodeTakenException extends ConflictException
{
    public function __construct()
    {
        parent::__construct('PROFILE_CODE_TAKEN', 'Já existe um perfil com este código.');
    }
}
