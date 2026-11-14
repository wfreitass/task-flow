<?php

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\ForbiddenException;

final class SystemProfileProtectedException extends ForbiddenException
{
    public function __construct()
    {
        parent::__construct('PROFILE_PROTECTED', 'Os perfis de sistema (admin e executor) não podem ser excluídos.');
    }
}
