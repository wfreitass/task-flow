<?php

namespace App\User\Presentation\Http\Response;

use App\User\Application\DTO\UserOutput;
use App\User\Application\DTO\UserSummaryOutput;

final class UserResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function fromOutput(UserOutput $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
        ];
    }

    /**
     * Visão pública de um usuário: só id e nome.
     *
     * @return array<string, mixed>
     */
    public static function summary(UserSummaryOutput $user): array
    {
        return ['id' => $user->id, 'username' => $user->username];
    }
}
