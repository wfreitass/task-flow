<?php

namespace App\User\Infrastructure\Persistence\Doctrine;

use App\User\Domain\Entity\User;
use App\User\Domain\Exception\EmailAlreadyRegisteredException;
use App\User\Domain\Exception\UsernameAlreadyTakenException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\User\Infrastructure\Persistence\Doctrine\Mapper\UserMapper;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserMapper $mapper,
    ) {
    }

    public function findById(int $id): ?User
    {
        return $this->toDomain($this->entityManager->find(UserEntity::class, $id));
    }

    public function findByUsername(string $username): ?User
    {
        return $this->toDomain($this->entityManager->getRepository(UserEntity::class)->findOneBy(['username' => $username]));
    }

    public function findByEmail(string $email): ?User
    {
        return $this->toDomain($this->entityManager->getRepository(UserEntity::class)->findOneBy(['email' => mb_strtolower(trim($email))]));
    }

    public function findActive(): array
    {
        $entities = $this->entityManager->getRepository(UserEntity::class)->findBy(['isActive' => true], ['username' => 'ASC', 'id' => 'ASC']);

        return array_map(fn (UserEntity $entity): User => $this->mapper->toDomain($entity), $entities);
    }

    public function findByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $entities = $this->entityManager->getRepository(UserEntity::class)->findBy(['id' => $ids]);

        return array_map(fn (UserEntity $entity): User => $this->mapper->toDomain($entity), $entities);
    }

    public function searchActive(string $query, int $limit): array
    {
        $query = trim($query);

        /** @var list<UserEntity> $entities */
        $entities = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(UserEntity::class, 'u')
            ->where('u.isActive = true')
            ->andWhere('(u.username LIKE :like OR u.email = :email)')
            // Escapa os curingas do LIKE: a busca é por texto literal.
            ->setParameter('like', '%'.addcslashes($query, '%_\\').'%')
            ->setParameter('email', mb_strtolower($query))
            ->orderBy('u.username', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(fn (UserEntity $entity): User => $this->mapper->toDomain($entity), $entities);
    }

    public function add(User $user): void
    {
        $entity = $this->mapper->toEntity($user);

        try {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            // Duas requisições simultâneas passaram pela verificação prévia: quem perdeu a corrida chega aqui.
            // Após uma falha no flush o EntityManager fica fechado; a exceção encerra a requisição.
            throw str_contains($exception->getMessage(), 'uq_users_email')
                ? new EmailAlreadyRegisteredException()
                : new UsernameAlreadyTakenException();
        }

        $this->mapper->syncGeneratedId($user, $entity);
    }

    private function toDomain(?UserEntity $entity): ?User
    {
        return null === $entity ? null : $this->mapper->toDomain($entity);
    }
}
