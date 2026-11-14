<?php

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class ProfileNotFoundException extends NotFoundException
{
    public function __construct()
    {
        parent::__construct('PROFILE_NOT_FOUND', 'Perfil não encontrado.');
    }
}
