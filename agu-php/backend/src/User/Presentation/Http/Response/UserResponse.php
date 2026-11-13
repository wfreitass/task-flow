<?php

namespace App\User\Presentation\Http\Response;

use App\User\Application\DTO\UserOutput;

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
        ];
    }
}
