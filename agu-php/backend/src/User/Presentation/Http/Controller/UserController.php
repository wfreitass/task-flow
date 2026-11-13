<?php

namespace App\User\Presentation\Http\Controller;

use App\Shared\Presentation\Http\RequestValidator;
use App\User\Application\DTO\UserSummaryOutput;
use App\User\Application\Service\ListUsersService;
use App\User\Application\Service\SearchUsersService;
use App\User\Presentation\Http\Request\SearchUsersQuery;
use App\User\Presentation\Http\Response\UserResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Consulta de usuários. Exige JWT (firewall "api"). Cadastro, login, logout e o usuário logado (`me`)
 * ficam no AuthController.
 */
#[AsController]
#[Route('/api')]
final class UserController
{
    #[Route('/users/list', name: 'user_list', methods: ['GET'])]
    public function list(ListUsersService $listUsers): JsonResponse
    {
        return new JsonResponse(['data' => array_map(static fn (UserSummaryOutput $user): array => UserResponse::summary($user), $listUsers->execute())]);
    }

    /**
     * Busca usuários ativos para escolher, por exemplo, o responsável por uma tarefa. Devolve só id e nome.
     */
    #[Route('/users', name: 'user_search', methods: ['GET'])]
    public function search(Request $request, SearchUsersService $searchUsers, RequestValidator $validator): JsonResponse
    {
        $query = SearchUsersQuery::fromArray($request->query->all());
        $validator->validate($query);

        return new JsonResponse([
            'data' => array_map(
                static fn (UserSummaryOutput $user): array => UserResponse::summary($user),
                $searchUsers->execute($query->text()),
            ),
        ]);
    }
}
