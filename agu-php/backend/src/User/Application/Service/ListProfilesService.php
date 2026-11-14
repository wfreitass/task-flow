<?php

namespace App\User\Application\Service;

use App\Shared\Application\Port\RoleCheckerInterface;
use App\Shared\Domain\Role;
use App\User\Application\DTO\ProfileOutput;
use App\User\Application\DTO\RoleOutput;
use App\User\Domain\Entity\Profile;
use App\User\Domain\Repository\ProfileRepositoryInterface;
use App\User\Domain\ValueObject\RoleDefinition;

/**
 * Lista os profiles (com as roles de cada um) e o catálogo de roles. Quem pode vincular profiles a usuários
 * (`user.manage`) precisa ver as opções; criar e editar profiles exige `profile.manage`.
 */
final class ListProfilesService
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profiles,
        private readonly RoleCheckerInterface $roles,
    ) {
    }

    /**
     * @return list<ProfileOutput>
     */
    public function profiles(int $actorId): array
    {
        $this->roles->assertHas($actorId, Role::UserManage);

        return array_map(static fn (Profile $profile): ProfileOutput => ProfileOutput::fromProfile($profile), $this->profiles->findAll());
    }

    /**
     * @return list<RoleOutput>
     */
    public function roles(int $actorId): array
    {
        $this->roles->assertHas($actorId, Role::UserManage);

        return array_map(static fn (RoleDefinition $definition): RoleOutput => RoleOutput::fromDefinition($definition), $this->profiles->roleCatalog());
    }
}
