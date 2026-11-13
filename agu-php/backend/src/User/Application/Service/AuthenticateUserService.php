<?php

namespace App\User\Application\Service;

use App\User\Application\DTO\AccessTokenOutput;
use App\User\Application\DTO\AuthenticateUserInput;
use App\User\Application\Port\PasswordHasherInterface;
use App\User\Application\Port\TokenIssuerInterface;
use App\User\Domain\Exception\InvalidCredentialsException;
use App\User\Domain\Repository\UserRepositoryInterface;

final class AuthenticateUserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly TokenIssuerInterface $tokenIssuer,
    ) {
    }

    public function execute(AuthenticateUserInput $input): AccessTokenOutput
    {
        $user = $this->users->findByUsername(trim($input->username));

        if (null === $user) {
            // Gasta o mesmo custo de uma verificação real, para que o tempo de resposta
            // não revele se o usuário existe.
            $this->passwordHasher->hash($input->password);

            throw new InvalidCredentialsException();
        }

        if (!$user->isActive() || !$this->passwordHasher->verify($user->passwordHash(), $input->password)) {
            throw new InvalidCredentialsException();
        }

        return new AccessTokenOutput($this->tokenIssuer->issue($user));
    }
}
