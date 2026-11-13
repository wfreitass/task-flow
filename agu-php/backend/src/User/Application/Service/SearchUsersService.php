<?php

namespace App\User\Application\Service;

use App\User\Application\DTO\UserSummaryOutput;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;

final class SearchUsersService
{
    /** Máximo de resultados por busca: é uma ajuda para escolher alguém, não uma listagem de todos os usuários. */
    public const MAX_RESULTS = 10;

    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    /**
     * Busca usuários ativos pelo nome de usuário (contém o texto) ou pelo e-mail exato. Só devolve id e nome.
     *
     * @return list<UserSummaryOutput>
     */
    public function execute(string $query): array
    {
        return array_map(
            static fn (User $user): UserSummaryOutput => UserSummaryOutput::fromUser($user),
            $this->users->searchActive($query, self::MAX_RESULTS),
        );
    }
}
