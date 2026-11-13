<?php

namespace App\Task\Presentation\Http\Response;

use App\Task\Application\DTO\AttachmentOutput;
use App\Task\Application\DTO\TaskOutput;
use App\Task\Application\DTO\TaskPageOutput;

/**
 * Contrato HTTP de saída. Nomes de campos da API; não expõe colunas nem entidades.
 */
final class TaskResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function fromOutput(TaskOutput $task): array
    {
        $response = [
            'id' => $task->id,
            'createdBy' => $task->createdBy,
            'assignee' => null === $task->assignee ? null : ['id' => $task->assignee->id, 'username' => $task->assignee->username],
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority->value,
            'status' => $task->status->value,
            'startAt' => $task->startDate?->format(\DATE_ATOM),
            'dueAt' => $task->dueDate?->format(\DATE_ATOM),
            'completedAt' => $task->completedAt?->format(\DATE_ATOM),
            'createdAt' => $task->createdAt->format(\DATE_ATOM),
            'updatedAt' => $task->updatedAt->format(\DATE_ATOM),
        ];

        // Só quando a operação carregou os anexos (hoje, na criação).
        if (null !== $task->attachments) {
            $response['attachments'] = array_map(static fn (AttachmentOutput $attachment): array => [
                'id' => $attachment->id,
                'name' => $attachment->originalName,
                'mimeType' => $attachment->mimeType,
                'sizeBytes' => $attachment->sizeBytes,
                'createdAt' => $attachment->createdAt->format(\DATE_ATOM),
            ], $task->attachments);
        }

        return $response;
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public static function fromPage(TaskPageOutput $page): array
    {
        return [
            'data' => array_map(self::fromOutput(...), $page->items),
            'meta' => [
                'page' => $page->page,
                'perPage' => $page->perPage,
                'total' => $page->total,
                'totalPages' => $page->totalPages(),
            ],
        ];
    }
}
