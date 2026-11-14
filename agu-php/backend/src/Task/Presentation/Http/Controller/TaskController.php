<?php

namespace App\Task\Presentation\Http\Controller;

use App\Shared\Application\Port\CurrentUserIdProviderInterface;
use App\Shared\Presentation\Http\RequestPayloadDecoder;
use App\Shared\Presentation\Http\RequestValidator;
use App\Task\Application\Service\ChangeTaskStatusService;
use App\Task\Application\Service\CreateTaskService;
use App\Task\Application\Service\DeleteTaskService;
use App\Task\Application\Service\GetTaskService;
use App\Task\Application\Service\ListTasksService;
use App\Task\Application\Service\ListTaskActivityService;
use App\Task\Application\Service\UpdateTaskService;
use App\Task\Presentation\Http\Request\ChangeTaskStatusRequest;
use App\Task\Presentation\Http\Request\CreateTaskRequest;
use App\Task\Presentation\Http\Request\ListTasksQuery;
use App\Task\Presentation\Http\Request\UpdateTaskRequest;
use App\Task\Presentation\Http\Response\TaskResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Tarefas do usuário autenticado. O dono vem sempre do token; tarefa de outro usuário responde 404.
 */
#[AsController]
#[Route('/api/tasks')]
final class TaskController
{
    public function __construct(
        private readonly CurrentUserIdProviderInterface $currentUser,
        private readonly RequestPayloadDecoder $payload,
        private readonly RequestValidator $validator,
    ) {
    }

    #[Route('/{id}/history', name: 'task_history_list', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function history(int $id, ListTaskActivityService $activity): JsonResponse
    {
        return new JsonResponse(['data' => $activity->execute($id, $this->currentUser->currentUserId(), true)]);
    }

    #[Route('', name: 'task_list', methods: ['GET'])]
    public function list(Request $request, ListTasksService $listTasks): JsonResponse
    {
        $query = ListTasksQuery::fromArray($request->query->all());
        $this->validator->validate($query);

        return new JsonResponse(TaskResponse::fromPage($listTasks->execute($query->toInput(), $this->currentUser->currentUserId())));
    }

    #[Route('', name: 'task_create', methods: ['POST'])]
    public function create(Request $request, CreateTaskService $createTask): JsonResponse
    {
        $input = CreateTaskRequest::fromArray($this->payload->decode($request), $this->uploadedFiles($request));
        $this->validator->validate($input);

        $task = $createTask->execute($input->toInput(), $this->currentUser->currentUserId());

        return new JsonResponse(TaskResponse::fromOutput($task), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'task_get', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function get(int $id, GetTaskService $getTask): JsonResponse
    {
        return new JsonResponse(TaskResponse::fromOutput($getTask->execute($id, $this->currentUser->currentUserId())));
    }

    #[Route('/{id}', name: 'task_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(int $id, Request $request, UpdateTaskService $updateTask): JsonResponse
    {
        $input = UpdateTaskRequest::fromArray($this->payload->decode($request));
        $this->validator->validate($input);

        $task = $updateTask->execute($input->toInput($id), $this->currentUser->currentUserId());

        return new JsonResponse(TaskResponse::fromOutput($task));
    }

    #[Route('/{id}/status', name: 'task_change_status', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function changeStatus(int $id, Request $request, ChangeTaskStatusService $changeStatus): JsonResponse
    {
        $input = ChangeTaskStatusRequest::fromArray($this->payload->decode($request));
        $this->validator->validate($input);

        $task = $changeStatus->execute($input->toInput($id), $this->currentUser->currentUserId());

        return new JsonResponse(TaskResponse::fromOutput($task));
    }

    #[Route('/{id}', name: 'task_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id, DeleteTaskService $deleteTask): Response
    {
        $deleteTask->execute($id, $this->currentUser->currentUserId());

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Arquivos do campo `attachments`, em lista. Um único arquivo ou vários (attachments[]) viram uma lista.
     *
     * @return list<mixed>
     */
    private function uploadedFiles(Request $request): array
    {
        $files = $request->files->get('attachments');

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        return \is_array($files) ? array_values(array_filter($files, static fn (mixed $file): bool => null !== $file)) : [];
    }
}
