<?php

namespace App\Task\Presentation\Http\Request;

use App\Task\Application\DTO\CreateTaskInput;
use App\Task\Domain\Enum\TaskPriority;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contrato HTTP de POST /api/tasks. Valida o formato; regras de negócio ficam no domínio.
 * Propriedades `mixed` permitem rejeitar tipos errados (ex.: número no lugar de texto) como erro de validação.
 */
final class CreateTaskRequest
{
    #[Assert\NotBlank(message: 'O título é obrigatório.')]
    #[Assert\Type(type: 'string', message: 'O título deve ser um texto.')]
    #[Assert\Length(max: 255, maxMessage: 'O título deve ter no máximo {{ limit }} caracteres.')]
    public mixed $title = null;

    #[Assert\Type(type: 'string', message: 'A descrição deve ser um texto.')]
    public mixed $description = null;

    #[Assert\Type(type: 'string', message: 'A prioridade deve ser um texto.')]
    #[Assert\Choice(callback: [TaskPriority::class, 'values'], message: 'Prioridade inválida. Valores aceitos: low, medium, high, urgent.')]
    public mixed $priority = null;

    #[Assert\Type(type: 'string', message: 'A data de vencimento deve ser um texto.')]
    #[Assert\DateTime(format: \DATE_ATOM, message: 'A data de vencimento deve estar no formato ISO 8601 (ex.: 2026-12-01T18:00:00-03:00).')]
    public mixed $dueAt = null;

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $request = new self();
        $request->title = $payload['title'] ?? null;
        $request->description = $payload['description'] ?? null;
        $request->priority = $payload['priority'] ?? null;
        $request->dueAt = $payload['dueAt'] ?? null;

        return $request;
    }

    /**
     * Só deve ser chamado depois de a validação passar.
     */
    public function toInput(): CreateTaskInput
    {
        return new CreateTaskInput(
            (string) $this->title,
            null === $this->description ? null : (string) $this->description,
            null === $this->priority ? TaskPriority::Medium : TaskPriority::from((string) $this->priority),
            null === $this->dueAt ? null : new \DateTimeImmutable((string) $this->dueAt),
        );
    }
}
