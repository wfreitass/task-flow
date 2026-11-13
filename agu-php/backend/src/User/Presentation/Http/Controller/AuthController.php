<?php

namespace App\User\Presentation\Http\Controller;

use App\Shared\Presentation\Http\JsonPayloadDecoder;
use App\Shared\Presentation\Http\RequestValidator;
use App\User\Application\Service\AuthenticateUserService;
use App\User\Application\Service\GetCurrentUserService;
use App\User\Application\Service\RegisterUserService;
use App\User\Presentation\Http\Request\LoginRequest;
use App\User\Presentation\Http\Request\RegisterUserRequest;
use App\User\Presentation\Http\Response\UserResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Rotas de autenticação. `register` e `login` são públicas (firewall "public" em config/packages/security.yaml);
 * `me` e `logout` exigem JWT (firewall "api").
 */
#[AsController]
#[Route('/api')]
final class AuthController
{
    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        RegisterUserService $registerUser,
        JsonPayloadDecoder $payload,
        RequestValidator $validator,
    ): JsonResponse {
        $input = RegisterUserRequest::fromArray($payload->decode($request));
        $validator->validate($input);

        $user = $registerUser->execute($input->toInput());

        return new JsonResponse(UserResponse::fromOutput($user), Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'auth_login', methods: ['POST'])]
    public function login(
        Request $request,
        AuthenticateUserService $authenticateUser,
        JsonPayloadDecoder $payload,
        RequestValidator $validator,
    ): JsonResponse {
        $input = LoginRequest::fromArray($payload->decode($request));
        $validator->validate($input);

        $accessToken = $authenticateUser->execute($input->toInput());

        return new JsonResponse(['token' => $accessToken->token]);
    }

    #[Route('/me', name: 'auth_me', methods: ['GET'])]
    public function me(GetCurrentUserService $getCurrentUser): JsonResponse
    {
        return new JsonResponse(UserResponse::fromOutput($getCurrentUser->execute()));
    }

    /**
     * Logout sem estado: o JWT é validado pelo firewall e o cliente descarta o token.
     * O servidor não guarda sessão nem lista de revogados, então o token continua válido até expirar
     * (`token_ttl`, 1 hora). Invalidar no servidor exigiria um armazenamento de tokens revogados.
     */
    #[Route('/logout', name: 'auth_logout', methods: ['POST'])]
    public function logout(): Response
    {
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
