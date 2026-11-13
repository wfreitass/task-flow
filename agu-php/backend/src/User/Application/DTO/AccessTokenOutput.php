<?php

namespace App\User\Application\DTO;

final class AccessTokenOutput
{
    public function __construct(public readonly string $token)
    {
    }
}
