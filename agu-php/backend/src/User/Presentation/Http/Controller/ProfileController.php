<?php

namespace App\User\Presentation\Http\Controller;

use App\Shared\Application\Port\CurrentUserIdProviderInterface;
use App\Shared\Presentation\Http\JsonPayloadDecoder;
use App\Shared\Presentation\Http\RequestValidator;
use App\User\Application\DTO\ProfileOutput;
use App\User\Application\DTO\RoleOutput;
use App\User\Application\Service\CreateProfileService;
use App\User\Application\Service\DeleteProfileService;
use App\User\Application\Service\ListProfilesService;
use App\User\Application\Service\UpdateProfileService;
use App\User\Presentation\Http\Request\CreateProfileRequest;
use App\User\Presentation\Http\Request\UpdateProfileRequest;
use App\User\Presentation\Http\Response\ProfileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * RBAC: profiles (perfis) e o catálogo de roles. Exige JWT. Listar exige a role `user.manage`; criar, editar e
 * excluir profiles exige `profile.manage`. Sem a role, 403 `PERMISSION_DENIED`.
 */
#[AsController]
#[Route('/api')]
final class ProfileController
{
    public function __construct(private readonly CurrentUserIdProviderInterface $currentUser)
    {
    }

    /** O catálogo de roles que existem. */
    #[Route('/roles', name: 'role_list', methods: ['GET'])]
    public function roles(ListProfilesService $list): JsonResponse
    {
        return new JsonResponse(['data' => array_map(
            static fn (RoleOutput $role): array => ProfileResponse::role($role),
            $list->roles($this->currentUser->currentUserId()),
        )]);
    }

    /** Os profiles, cada um com as suas roles. */
    #[Route('/profiles', name: 'profile_list', methods: ['GET'])]
    public function list(ListProfilesService $list): JsonResponse
    {
        return new JsonResponse(['data' => array_map(
            static fn (ProfileOutput $profile): array => ProfileResponse::fromOutput($profile),
            $list->profiles($this->currentUser->currentUserId()),
        )]);
    }

    #[Route('/profiles', name: 'profile_create', methods: ['POST'])]
    public function create(Request $request, CreateProfileService $create, JsonPayloadDecoder $payload, RequestValidator $validator): JsonResponse
    {
        $input = CreateProfileRequest::fromArray($payload->decode($request));
        $validator->validate($input);

        return new JsonResponse(ProfileResponse::fromOutput($create->execute($this->currentUser->currentUserId(), $input->toInput())), Response::HTTP_CREATED);
    }

    /** Muda nome, descrição e/ou as roles do profile. Vale na hora para todos os usuários que o têm. */
    #[Route('/profiles/{id}', name: 'profile_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(int $id, Request $request, UpdateProfileService $update, JsonPayloadDecoder $payload, RequestValidator $validator): JsonResponse
    {
        $input = UpdateProfileRequest::fromArray($payload->decode($request));
        $validator->validate($input);

        return new JsonResponse(ProfileResponse::fromOutput($update->execute($this->currentUser->currentUserId(), $input->toInput($id))));
    }

    #[Route('/profiles/{id}', name: 'profile_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id, DeleteProfileService $delete): Response
    {
        $delete->execute($this->currentUser->currentUserId(), $id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
