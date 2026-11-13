<?php

namespace App\User\Application\DTO;

final class AuthenticateUserInput
{
    public function __construct(
        public readonly string $username,
        public readonly string $password,
    ) {
    }
}
