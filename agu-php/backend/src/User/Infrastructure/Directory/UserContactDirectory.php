<?php

namespace App\User\Infrastructure\Directory;

use App\Task\Application\DTO\UserContact;
use App\Task\Application\Port\UserContactInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

/**
 * Implementa, para o contexto Task, a consulta do contato de um usuário (inclui o e-mail), usada só nas notificações.
 */
final class UserContactDirectory implements UserContactInterface
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    public function findActiveContactById(int $id): ?UserContact
    {
        $user = $this->users->findById($id);

        return null !== $user && $user->isActive() ? new UserContact((int) $user->id(), $user->username(), $user->email()) : null;
    }
}
