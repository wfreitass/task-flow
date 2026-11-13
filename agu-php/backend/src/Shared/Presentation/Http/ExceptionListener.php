<?php

namespace App\Shared\Presentation\Http;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Presentation\Http\Exception\MalformedRequestException;
use App\Shared\Presentation\Http\Exception\ValidationFailedException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Converte exceções em respostas JSON padronizadas:
 * {"error": {"code": "...", "message": "...", "details": {...}}}.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ExceptionListener
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        [$status, $code, $message, $details] = match (true) {
            $exception instanceof MalformedRequestException => [Response::HTTP_BAD_REQUEST, 'MALFORMED_REQUEST', 'O corpo da requisição não é um JSON válido.', []],
            $exception instanceof ValidationFailedException => [Response::HTTP_UNPROCESSABLE_ENTITY, 'VALIDATION_FAILED', 'Dados inválidos.', $this->violations($exception)],
            $exception instanceof DomainException => [Response::HTTP_UNPROCESSABLE_ENTITY, $exception->errorCode(), $exception->getMessage(), $exception->details()],
            $exception instanceof AuthenticationException => [Response::HTTP_UNAUTHORIZED, 'UNAUTHENTICATED', 'Autenticação necessária.', []],
            $exception instanceof AccessDeniedException => [Response::HTTP_FORBIDDEN, 'FORBIDDEN', 'Acesso negado.', []],
            $exception instanceof NotFoundHttpException => [Response::HTTP_NOT_FOUND, 'NOT_FOUND', 'Recurso não encontrado.', []],
            $exception instanceof HttpExceptionInterface => [$exception->getStatusCode(), 'HTTP_ERROR', Response::$statusTexts[$exception->getStatusCode()] ?? 'Erro HTTP.', []],
            default => [Response::HTTP_INTERNAL_SERVER_ERROR, 'INTERNAL_ERROR', 'Erro interno.', []],
        };

        if (Response::HTTP_INTERNAL_SERVER_ERROR === $status) {
            $this->logger->error('Erro inesperado na requisição: {message}', ['message' => $exception->getMessage(), 'exception' => $exception]);
        }

        $event->setResponse(new JsonResponse(
            ['error' => ['code' => $code, 'message' => $message, 'details' => (object) $details]],
            $status,
        ));
    }

    /**
     * @return array<string, string>
     */
    private function violations(ValidationFailedException $exception): array
    {
        $details = [];
        foreach ($exception->violations as $violation) {
            $details[$violation->getPropertyPath()] = (string) $violation->getMessage();
        }

        return $details;
    }
}
