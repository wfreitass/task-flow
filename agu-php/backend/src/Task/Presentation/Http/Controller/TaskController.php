<?php

namespace App\Task\Presentation\Http\Controller;

use App\Shared\Presentation\Http\JsonPayloadDecoder;
use App\Shared\Presentation\Http\RequestValidator;
use App\Task\Application\Port\CurrentUserIdProviderInterface;
use App\Task\Application\Service\CreateTaskService;
use App\Task\Presentation\Http\Request\CreateTaskRequest;
use App\Task\Presentation\Http\Response\TaskResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[Route('/api/tasks')]
final class TaskController
{
    #[Route('', name: 'task_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateTaskService $createTask,
        CurrentUserIdProviderInterface $currentUser,
        JsonPayloadDecoder $payload,
        RequestValidator $validator,
    ): JsonResponse {
        $input = CreateTaskRequest::fromArray($payload->decode($request));
        $validator->validate($input);

        $task = $createTask->execute($input->toInput(), $currentUser->currentUserId());

        return new JsonResponse(TaskResponse::fromOutput($task), Response::HTTP_CREATED);
    }
}
