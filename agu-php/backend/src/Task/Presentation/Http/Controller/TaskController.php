<?php

namespace App\Task\Presentation\Http\Controller;

use App\Shared\Presentation\Http\Exception\MalformedRequestException;
use App\Shared\Presentation\Http\Exception\ValidationFailedException;
use App\Task\Application\Port\CurrentUserIdProviderInterface;
use App\Task\Application\Service\CreateTaskService;
use App\Task\Presentation\Http\Request\CreateTaskRequest;
use App\Task\Presentation\Http\Response\TaskResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
#[Route('/api/tasks')]
final class TaskController
{
    #[Route('', name: 'task_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateTaskService $createTask,
        CurrentUserIdProviderInterface $currentUser,
        ValidatorInterface $validator,
    ): JsonResponse {
        try {
            $payload = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new MalformedRequestException();
        }
        if (!is_array($payload)) {
            throw new MalformedRequestException();
        }

        $input = CreateTaskRequest::fromArray($payload);
        $violations = $validator->validate($input);
        if (\count($violations) > 0) {
            throw new ValidationFailedException($violations);
        }

        $task = $createTask->execute($input->toInput(), $currentUser->currentUserId());

        return new JsonResponse(TaskResponse::fromOutput($task), Response::HTTP_CREATED);
    }
}
