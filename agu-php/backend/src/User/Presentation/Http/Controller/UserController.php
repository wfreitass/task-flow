<?php

namespace App\User\Presentation\Http\Controller;

use App\Shared\Application\Port\CurrentUserIdProviderInterface;
use App\Shared\Presentation\Http\JsonPayloadDecoder;
use App\Shared\Presentation\Http\RequestValidator;
use App\User\Application\DTO\UserSummaryOutput;
use App\User\Application\Service\ChangeUserRoleService;
use App\User\Application\Service\ListUsersService;
use App\User\Application\Service\SearchUsersService;
use App\User\Presentation\Http\Request\ChangeUserRoleRequest;
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

    /**
     * Muda o perfil de um usuário (admin ou executor). Só admin (permissão `user.manage`); outro perfil recebe 403
     * `PERMISSION_DENIED`. O último admin ativo não pode ser rebaixado (409 `LAST_ADMIN`).
     */
    #[Route('/users/{id}/role', name: 'user_change_role', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function changeRole(
        int $id,
        Request $request,
        ChangeUserRoleService $changeRole,
        CurrentUserIdProviderInterface $currentUser,
        JsonPayloadDecoder $payload,
        RequestValidator $validator,
    ): JsonResponse {
        $input = ChangeUserRoleRequest::fromArray($payload->decode($request));
        $validator->validate($input);

        return new JsonResponse(UserResponse::fromOutput($changeRole->asUser($currentUser->currentUserId(), $id, $input->role())));
    }
}
