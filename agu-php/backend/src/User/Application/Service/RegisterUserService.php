<?php

namespace App\User\Application\Service;

use App\Shared\Application\Port\ClockInterface;
use App\User\Application\DTO\RegisterUserInput;
use App\User\Application\DTO\UserOutput;
use App\User\Application\Port\PasswordHasherInterface;
use App\User\Domain\Entity\Profile;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\EmailAlreadyRegisteredException;
use App\User\Domain\Exception\UsernameAlreadyTakenException;
use App\User\Domain\Repository\ProfileRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final class RegisterUserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly ProfileRepositoryInterface $profiles,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function execute(RegisterUserInput $input): UserOutput
    {
        // Todo cadastro novo recebe o profile padrão (executor); quem vira admin é promovido depois.
        $profile = $this->profiles->findByCode(Profile::DEFAULT_CODE)
            ?? throw new \LogicException('O profile padrão "'.Profile::DEFAULT_CODE.'" não existe: rode as migrations.');

        // A senha só é transformada em hash aqui; o domínio e o banco nunca guardam o texto puro.
        $user = User::register(
            $input->username,
            $input->email,
            $this->passwordHasher->hash($input->password),
            (int) $profile->id(),
            $this->clock->now(),
        );

        // Verificação antecipada para devolver erro claro; a corrida entre duas requisições
        // simultâneas é coberta pelas constraints únicas, traduzidas pelo repositório.
        if (null !== $this->users->findByUsername($user->username())) {
            throw new UsernameAlreadyTakenException();
        }
        if (null !== $this->users->findByEmail($user->email())) {
            throw new EmailAlreadyRegisteredException();
        }

        $this->users->add($user);

        return UserOutput::fromUser($user, $profile);
    }
}
