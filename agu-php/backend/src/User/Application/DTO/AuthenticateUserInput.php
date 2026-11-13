<?php

namespace App\User\Application\DTO;

final class AuthenticateUserInput
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {
    }
}
