<?php

namespace App\User\Infrastructure\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Troca as respostas 401 do LexikJWT pelo formato de erro da API:
 * {"error": {"code": "...", "message": "...", "details": {}}}.
 */
final class JwtFailureResponseListener
{
    #[AsEventListener(event: Events::JWT_NOT_FOUND)]
    public function onTokenNotFound(JWTNotFoundEvent $event): void
    {
        $this->reply($event, 'TOKEN_NOT_FOUND', 'Token de acesso não informado.');
    }

    #[AsEventListener(event: Events::JWT_INVALID)]
    public function onTokenInvalid(JWTInvalidEvent $event): void
    {
        $this->reply($event, 'TOKEN_INVALID', 'Token de acesso inválido.');
    }

    #[AsEventListener(event: Events::JWT_EXPIRED)]
    public function onTokenExpired(JWTExpiredEvent $event): void
    {
        $this->reply($event, 'TOKEN_EXPIRED', 'Token de acesso expirado.');
    }

    private function reply(AuthenticationFailureEvent $event, string $code, string $message): void
    {
        $response = new JsonResponse(
            ['error' => ['code' => $code, 'message' => $message, 'details' => new \stdClass()]],
            Response::HTTP_UNAUTHORIZED,
        );
        $response->headers->set('WWW-Authenticate', 'Bearer');

        $event->setResponse($response);
    }
}
