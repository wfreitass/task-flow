<?php

namespace App\User\Presentation\Http\Response;

use App\User\Application\DTO\ProfileOutput;
use App\User\Application\DTO\RoleOutput;

final class ProfileResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function fromOutput(ProfileOutput $profile): array
    {
        return [
            'id' => $profile->id,
            'code' => $profile->code,
            'name' => $profile->name,
            'description' => $profile->description,
            'isSystem' => $profile->isSystem,
            'roles' => $profile->roles,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function role(RoleOutput $role): array
    {
        return ['code' => $role->code, 'name' => $role->name, 'description' => $role->description];
    }
}
