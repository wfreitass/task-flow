<?php

namespace App\User\Presentation\Http\Controller;

use App\Shared\Application\Port\CurrentUserIdProviderInterface;
use App\Shared\Presentation\Http\JsonPayloadDecoder;
use App\Shared\Presentation\Http\RequestValidator;
use App\User\Application\DTO\UserSummaryOutput;
use App\User\Application\Service\ChangeUserProfileService;
use App\User\Application\Service\GetUserProfileService;
use App\User\Application\Service\ListUsersService;
use App\User\Application\Service\SearchUsersService;
use App\User\Presentation\Http\Request\ChangeUserProfileRequest;
use App\User\Presentation\Http\Request\ChangeUserRoleRequest;
use App\User\Presentation\Http\Request\SearchUsersQuery;
use App\User\Presentation\Http\Response\UserResponse;
use App\User\Presentation\Http\Response\ProfileResponse;
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
    #[Route('/users/{id}/profile', name: 'user_get_profile', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function profile(int $id, GetUserProfileService $getProfile, CurrentUserIdProviderInterface $currentUser): JsonResponse
    {
        return new JsonResponse(ProfileResponse::fromOutput($getProfile->execute($currentUser->currentUserId(), $id)));
    }

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
     * Vincula um profile a um usuário. Exige a role `user.manage` (403 `PERMISSION_DENIED` sem ela). Deixar o
     * sistema sem ninguém que gerencie usuários é recusado (409 `LAST_ADMIN`).
     */
    #[Route('/users/{id}/profile', name: 'user_change_profile', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function changeProfile(
        int $id,
        Request $request,
        ChangeUserProfileService $changeProfile,
        CurrentUserIdProviderInterface $currentUser,
        JsonPayloadDecoder $payload,
        RequestValidator $validator,
    ): JsonResponse {
        $input = ChangeUserProfileRequest::fromArray($payload->decode($request));
        $validator->validate($input);

        return new JsonResponse(UserResponse::fromOutput($changeProfile->asUser($currentUser->currentUserId(), $id, $input->profileId())));
    }

    /**
     * Endpoint antigo, mantido por compatibilidade com o frontend atual: recebe o CÓDIGO do profile (`admin` ou
     * `executor`) em `role`. Use PATCH /users/{id}/profile.
     */
    #[Route('/users/{id}/role', name: 'user_change_role', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function changeRole(
        int $id,
        Request $request,
        ChangeUserProfileService $changeProfile,
        CurrentUserIdProviderInterface $currentUser,
        JsonPayloadDecoder $payload,
        RequestValidator $validator,
    ): JsonResponse {
        $input = ChangeUserRoleRequest::fromArray($payload->decode($request));
        $validator->validate($input);

        return new JsonResponse(UserResponse::fromOutput($changeProfile->asUserByProfileCode($currentUser->currentUserId(), $id, $input->profileCode())));
    }
}
