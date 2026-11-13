<?php

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\User;
use App\User\Domain\Exception\EmailAlreadyRegisteredException;
use App\User\Domain\Exception\UsernameAlreadyTakenException;

interface UserRepositoryInterface
{
    public function findByUsername(string $username): ?User;

    public function findByEmail(string $email): ?User;

    /**
     * Persiste um novo usuário.
     *
     * @throws UsernameAlreadyTakenException se o nome de usuário já existir (inclusive em corrida entre requisições)
     * @throws EmailAlreadyRegisteredException se o e-mail já existir
     */
    public function add(User $user): void;
}
