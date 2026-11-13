<?php

namespace App\User\Infrastructure\Directory;

use App\Task\Application\DTO\DirectoryUser;
use App\Task\Application\Port\UserDirectoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;

/**
 * Implementa, para o contexto Task, a consulta de usuários. O contexto Task só recebe id e nome.
 */
final class UserDirectory implements UserDirectoryInterface
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    public function findActiveById(int $id): ?DirectoryUser
    {
        $user = $this->users->findById($id);

        return null !== $user && $user->isActive() ? $this->toDirectoryUser($user) : null;
    }

    public function findByIds(array $ids): array
    {
        $found = [];
        foreach ($this->users->findByIds($ids) as $user) {
            $found[(int) $user->id()] = $this->toDirectoryUser($user);
        }

        return $found;
    }

    private function toDirectoryUser(User $user): DirectoryUser
    {
        return new DirectoryUser((int) $user->id(), $user->username());
    }
}
