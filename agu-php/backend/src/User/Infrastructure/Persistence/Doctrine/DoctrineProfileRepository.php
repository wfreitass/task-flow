<?php

namespace App\User\Infrastructure\Persistence\Doctrine;

use App\Shared\Domain\Role;
use App\User\Domain\Entity\Profile;
use App\User\Domain\Exception\ProfileCodeTakenException;
use App\User\Domain\Repository\ProfileRepositoryInterface;
use App\User\Domain\ValueObject\RoleDefinition;
use App\User\Infrastructure\Persistence\Doctrine\Entity\ProfileEntity;
use App\User\Infrastructure\Persistence\Doctrine\Entity\RoleEntity;
use App\User\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\User\Infrastructure\Persistence\Doctrine\Mapper\ProfileMapper;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineProfileRepository implements ProfileRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProfileMapper $mapper,
    ) {
    }

    public function findById(int $id): ?Profile
    {
        return $this->one($this->base()->where('p.id = :id')->setParameter('id', $id));
    }

    public function findByCode(string $code): ?Profile
    {
        return $this->one($this->base()->where('p.code = :code')->setParameter('code', $code));
    }

    public function findAll(): array
    {
        /** @var list<ProfileEntity> $entities */
        $entities = $this->base()->orderBy('p.name', 'ASC')->addOrderBy('p.id', 'ASC')->getQuery()->getResult();

        return array_map(fn (ProfileEntity $entity): Profile => $this->mapper->toDomain($entity), $entities);
    }

    public function findByActiveUserId(int $userId): ?Profile
    {
        // O usuário precisa estar ativo: um usuário desativado não tem role nenhuma.
        return $this->one(
            $this->base()
                ->where('p.id = (SELECT u.profileId FROM '.UserEntity::class.' u WHERE u.id = :user AND u.isActive = true)')
                ->setParameter('user', $userId),
        );
    }

    public function roleCatalog(): array
    {
        /** @var list<RoleEntity> $entities */
        $entities = $this->entityManager->getRepository(RoleEntity::class)->findBy([], ['id' => 'ASC']);

        $catalog = [];
        foreach ($entities as $entity) {
            $role = Role::tryFrom($entity->code);
            if (null !== $role) {
                $catalog[] = new RoleDefinition($role, $entity->name, $entity->description);
            }
        }

        return $catalog;
    }

    public function add(Profile $profile): void
    {
        $entity = $this->mapper->toEntity($profile);
        $this->mapper->syncRoles($entity, $this->roleEntities($profile));

        try {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            // Duas requisições simultâneas passaram pela verificação prévia: quem perdeu a corrida chega aqui.
            throw new ProfileCodeTakenException();
        }

        $this->mapper->syncGeneratedId($profile, $entity);
    }

    public function save(Profile $profile): void
    {
        $entity = $this->entityFor($profile);
        $this->mapper->fill($entity, $profile);
        $this->mapper->syncRoles($entity, $this->roleEntities($profile));
        $this->entityManager->flush();
    }

    public function remove(Profile $profile): void
    {
        $this->entityManager->remove($this->entityFor($profile));
        $this->entityManager->flush();
    }

    public function countUsers(int $profileId): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(UserEntity::class, 'u')
            ->where('u.profileId = :profile')
            ->setParameter('profile', $profileId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function base(): \Doctrine\ORM\QueryBuilder
    {
        // Traz as roles junto (um SELECT só), em vez de uma consulta extra por profile.
        return $this->entityManager->createQueryBuilder()
            ->select('p', 'r')
            ->from(ProfileEntity::class, 'p')
            ->leftJoin('p.roles', 'r');
    }

    private function one(\Doctrine\ORM\QueryBuilder $query): ?Profile
    {
        /** @var ProfileEntity|null $entity */
        $entity = $query->getQuery()->getOneOrNullResult();

        return null === $entity ? null : $this->mapper->toDomain($entity);
    }

    private function entityFor(Profile $profile): ProfileEntity
    {
        $entity = null === $profile->id() ? null : $this->entityManager->find(ProfileEntity::class, $profile->id());
        if (null === $entity) {
            throw new \LogicException('Só é possível gravar ou remover um perfil que já foi criado.');
        }

        return $entity;
    }

    /**
     * @return list<RoleEntity>
     */
    private function roleEntities(Profile $profile): array
    {
        $codes = array_map(static fn (Role $role): string => $role->value, $profile->roles());
        if ([] === $codes) {
            return [];
        }

        /** @var list<RoleEntity> $entities */
        $entities = $this->entityManager->getRepository(RoleEntity::class)->findBy(['code' => $codes]);

        return $entities;
    }
}
