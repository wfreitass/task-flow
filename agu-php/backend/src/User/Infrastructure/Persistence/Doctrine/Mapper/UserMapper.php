<?php

namespace App\User\Infrastructure\Persistence\Doctrine\Mapper;

use App\Shared\Infrastructure\Persistence\DomainObjectHydrator;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Persistence\Doctrine\Entity\UserEntity;

/**
 * Converte a entidade de domínio User e o modelo de persistência UserEntity.
 */
final class UserMapper
{
    public function __construct(private readonly DomainObjectHydrator $hydrator)
    {
    }

    public function toEntity(User $user): UserEntity
    {
        $entity = new UserEntity();
        $entity->username = $user->username();
        $entity->email = $user->email();
        $entity->passwordHash = $user->passwordHash();
        $entity->roles = $user->roles();
        $entity->isActive = $user->isActive();
        $entity->createdAt = $user->createdAt();
        $entity->updatedAt = $user->updatedAt();

        return $entity;
    }

    /**
     * Reconstrói o usuário a partir do que está no banco, sem refazer as validações de cadastro.
     */
    public function toDomain(UserEntity $entity): User
    {
        return $this->hydrator->hydrate(User::class, [
            'id' => $entity->id,
            'username' => $entity->username,
            'email' => $entity->email,
            'passwordHash' => $entity->passwordHash,
            'roles' => $entity->roles,
            'isActive' => $entity->isActive,
            'createdAt' => $entity->createdAt,
            'updatedAt' => $entity->updatedAt,
        ]);
    }

    /**
     * Depois do INSERT, devolve ao domínio o id que o banco gerou.
     */
    public function syncGeneratedId(User $user, UserEntity $entity): void
    {
        $this->hydrator->set($user, 'id', $entity->id);
    }
}
