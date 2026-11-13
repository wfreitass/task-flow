<?php

namespace App\Task\Presentation\Http\Request;

use App\Task\Application\DTO\ChangeTaskStatusInput;
use App\Task\Domain\Enum\TaskStatus;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contrato HTTP de PATCH /api/tasks/{id}/status.
 */
final class ChangeTaskStatusRequest
{
    #[Assert\NotBlank(message: 'O status é obrigatório.')]
    #[Assert\Type(type: 'string', message: 'O status deve ser um texto.')]
    #[Assert\Choice(callback: [TaskStatus::class, 'values'], message: 'Status inválido. Valores aceitos: backlog, todo, in_progress, blocked, done, canceled.')]
    public mixed $status = null;

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $request = new self();
        $request->status = $payload['status'] ?? null;

        return $request;
    }

    /**
     * Só deve ser chamado depois de a validação passar.
     */
    public function toInput(int $taskId): ChangeTaskStatusInput
    {
        return new ChangeTaskStatusInput($taskId, TaskStatus::from((string) $this->status));
    }
}
