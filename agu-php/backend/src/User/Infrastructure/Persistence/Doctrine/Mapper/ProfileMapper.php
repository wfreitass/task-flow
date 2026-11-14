<?php

namespace App\User\Infrastructure\Persistence\Doctrine\Mapper;

use App\Shared\Domain\Role;
use App\Shared\Infrastructure\Persistence\DomainObjectHydrator;
use App\User\Domain\Entity\Profile;
use App\User\Infrastructure\Persistence\Doctrine\Entity\ProfileEntity;
use App\User\Infrastructure\Persistence\Doctrine\Entity\RoleEntity;

/**
 * Converte o profile de domínio e o modelo de persistência ProfileEntity.
 * As roles (N:N) são sincronizadas pelo repositório, que sabe buscar as RoleEntity.
 */
final class ProfileMapper
{
    public function __construct(private readonly DomainObjectHydrator $hydrator)
    {
    }

    public function toEntity(Profile $profile): ProfileEntity
    {
        $entity = new ProfileEntity();
        $entity->code = $profile->code();
        $entity->isSystem = $profile->isSystem();
        $entity->createdAt = $profile->createdAt();
        $this->fill($entity, $profile);

        return $entity;
    }

    /**
     * Copia para a entidade o que pode mudar depois da criação (nome, descrição); as roles ficam com o repositório.
     */
    public function fill(ProfileEntity $entity, Profile $profile): void
    {
        $entity->name = $profile->name();
        $entity->description = $profile->description();
        $entity->updatedAt = $profile->updatedAt();
    }

    public function toDomain(ProfileEntity $entity): Profile
    {
        // Roles do banco que o código não conhece (ex.: criadas à mão) são ignoradas: o código não as checa.
        $roles = [];
        foreach ($entity->roles as $roleEntity) {
            $role = Role::tryFrom($roleEntity->code);
            if (null !== $role) {
                $roles[] = $role;
            }
        }
        $roles = array_values(array_filter(Role::cases(), static fn (Role $case): bool => \in_array($case, $roles, true)));

        return $this->hydrator->hydrate(Profile::class, [
            'id' => $entity->id,
            'code' => $entity->code,
            'name' => $entity->name,
            'description' => $entity->description,
            'isSystem' => $entity->isSystem,
            'roles' => $roles,
            'createdAt' => $entity->createdAt,
            'updatedAt' => $entity->updatedAt,
        ]);
    }

    public function syncGeneratedId(Profile $profile, ProfileEntity $entity): void
    {
        $this->hydrator->set($profile, 'id', $entity->id);
    }

    /**
     * Faz as roles da entidade serem exatamente as do profile.
     *
     * @param list<RoleEntity> $roleEntities as RoleEntity das roles do profile
     */
    public function syncRoles(ProfileEntity $entity, array $roleEntities): void
    {
        $entity->roles->clear();
        foreach ($roleEntities as $roleEntity) {
            $entity->roles->add($roleEntity);
        }
    }
}
