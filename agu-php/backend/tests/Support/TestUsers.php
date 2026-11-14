<?php

namespace App\Tests\Support;

use App\User\Domain\Entity\Profile;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\ProfileRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Cria um usuário de teste no banco de testes (com o profile padrão, "executor", que a migration já cadastra).
 */
final class TestUsers
{
    public const PASSWORD = 'senha-de-teste-123';

    public static function create(ContainerInterface $container, string $prefix = 'teste'): User
    {
        $username = $prefix.'_'.bin2hex(random_bytes(4));
        $profileId = (int) $container->get(ProfileRepositoryInterface::class)->findByCode(Profile::DEFAULT_CODE)->id();

        // Custo baixo do bcrypt só para acelerar os testes.
        $hash = password_hash(self::PASSWORD, \PASSWORD_BCRYPT, ['cost' => 4]);
        $user = User::register($username, $username.'@example.com', $hash, $profileId, new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $container->get(UserRepositoryInterface::class)->add($user);

        return $user;
    }
}
