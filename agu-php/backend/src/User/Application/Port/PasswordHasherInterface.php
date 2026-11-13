<?php

namespace App\User\Application\Port;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;

    public function verify(string $passwordHash, string $plainPassword): bool;
}
