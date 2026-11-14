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
            // O profile do usuário e as roles que ele dá. O frontend decide o que mostrar por `roles`.
            'profile' => ['id' => $user->profileId, 'code' => $user->profileCode, 'name' => $user->profileName],
            'roles' => $user->roles,
            // Compatibilidade com o frontend atual (admin/executor): é o código do profile. Prefira `profile` e `roles`.
            'role' => $user->profileCode,
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
