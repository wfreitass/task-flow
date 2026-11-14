<?php

namespace App\User\Application\Service;

use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\RoleCheckerInterface;
use App\Shared\Domain\Role;
use App\User\Application\DTO\CreateProfileInput;
use App\User\Application\DTO\ProfileOutput;
use App\User\Domain\Entity\Profile;
use App\User\Domain\Exception\ProfileCodeTakenException;
use App\User\Domain\Repository\ProfileRepositoryInterface;

final class CreateProfileService
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profiles,
        private readonly RoleCheckerInterface $roles,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Cria um profile e já o vincula às roles escolhidas. Exige a role `profile.manage`.
     */
    public function execute(int $actorId, CreateProfileInput $input): ProfileOutput
    {
        $this->roles->assertHas($actorId, Role::ProfileManage);

        $profile = Profile::create($input->code, $input->name, $input->description, $input->roles, $this->clock->now());

        // Verificação antecipada para devolver erro claro; a corrida é coberta pela constraint única do banco.
        if (null !== $this->profiles->findByCode($profile->code())) {
            throw new ProfileCodeTakenException();
        }

        $this->profiles->add($profile);

        return ProfileOutput::fromProfile($profile);
    }
}
