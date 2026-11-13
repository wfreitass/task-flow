<?php

namespace App\Task\Presentation\Http\Response;

use App\Task\Application\DTO\TaskOutput;

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
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority->value,
            'status' => $task->status->value,
            'dueAt' => $task->dueDate?->format(\DATE_ATOM),
            'createdAt' => $task->createdAt->format(\DATE_ATOM),
        ];
    }
}
