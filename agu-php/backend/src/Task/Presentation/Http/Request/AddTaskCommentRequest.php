<?php

namespace App\Task\Presentation\Http\Request;

use App\Task\Application\DTO\AddTaskCommentInput;
use App\Task\Domain\Entity\TaskComment;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contrato HTTP de POST /api/tasks/{id}/comments.
 */
final class AddTaskCommentRequest
{
    #[Assert\NotBlank(message: 'O comentário é obrigatório.')]
    #[Assert\Type(type: 'string', message: 'O comentário deve ser um texto.')]
    #[Assert\Length(max: TaskComment::MAX_LENGTH, maxMessage: 'O comentário deve ter no máximo {{ limit }} caracteres.')]
    public mixed $content = null;

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $request = new self();
        $request->content = $payload['content'] ?? null;

        return $request;
    }

    /**
     * Só deve ser chamado depois de a validação passar.
     */
    public function toInput(int $taskId): AddTaskCommentInput
    {
        return new AddTaskCommentInput($taskId, (string) $this->content);
    }
}
