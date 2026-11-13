<?php

namespace App\Task\Presentation\Http\Controller;

use App\Shared\Application\Port\CurrentUserIdProviderInterface;
use App\Shared\Presentation\Http\RequestPayloadDecoder;
use App\Shared\Presentation\Http\RequestValidator;
use App\Task\Application\Service\AddTaskCommentService;
use App\Task\Presentation\Http\Request\AddTaskCommentRequest;
use App\Task\Presentation\Http\Response\CommentResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Comentários de uma tarefa do usuário autenticado. Tarefa alheia, excluída ou inexistente responde 404.
 */
#[AsController]
#[Route('/api/tasks/{id}/comments', requirements: ['id' => '\d+'])]
final class TaskCommentController
{
    public function __construct(
        private readonly CurrentUserIdProviderInterface $currentUser,
        private readonly RequestPayloadDecoder $payload,
        private readonly RequestValidator $validator,
    ) {
    }

    #[Route('', name: 'task_comment_add', methods: ['POST'])]
    public function add(int $id, Request $request, AddTaskCommentService $addComment): JsonResponse
    {
        $input = AddTaskCommentRequest::fromArray($this->payload->decode($request));
        $this->validator->validate($input);

        $comment = $addComment->execute($input->toInput($id), $this->currentUser->currentUserId());

        return new JsonResponse(CommentResponse::fromOutput($comment), Response::HTTP_CREATED);
    }
}
