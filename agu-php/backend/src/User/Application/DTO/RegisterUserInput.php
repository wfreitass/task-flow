<?php

namespace App\User\Application\DTO;

final class RegisterUserInput
{
    public function __construct(
        public readonly string $username,
        public readonly string $email,
        public readonly string $password,
    ) {
    }
}
