<?php

namespace App\Task\Presentation\Http\Request;

use App\Task\Application\DTO\UpdateTaskInput;
use App\Task\Domain\Enum\TaskPriority;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Contrato HTTP de PATCH /api/tasks/{id}: alteração parcial. Só os campos enviados são alterados;
 * `description`, `startAt` e `dueAt` aceitam `null` para limpar o valor. O status tem rota própria.
 */
final class UpdateTaskRequest
{
    /** Campos da API → campos do UpdateTaskInput. */
    private const FIELDS = ['title' => 'title', 'description' => 'description', 'priority' => 'priority', 'startAt' => 'startDate', 'dueAt' => 'dueDate'];
    private const NULLABLE = ['description', 'startAt', 'dueAt'];

    /** @var array<string, mixed> campos enviados, já filtrados */
    private array $payload = [];

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $request = new self();
        $request->payload = array_intersect_key($payload, self::FIELDS);

        return $request;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if ([] === $this->payload) {
            $context->buildViolation('Informe ao menos um campo para alterar: title, description, priority, startAt ou dueAt.')
                ->atPath('body')
                ->addViolation();

            return;
        }

        $rules = [
            'title' => [
                new Assert\NotBlank(message: 'O título é obrigatório.'),
                new Assert\Type(type: 'string', message: 'O título deve ser um texto.'),
                new Assert\Length(max: 255, maxMessage: 'O título deve ter no máximo {{ limit }} caracteres.'),
            ],
            'description' => [new Assert\Type(type: 'string', message: 'A descrição deve ser um texto.')],
            'priority' => [
                new Assert\NotBlank(message: 'A prioridade é obrigatória.'),
                new Assert\Type(type: 'string', message: 'A prioridade deve ser um texto.'),
                new Assert\Choice(callback: [TaskPriority::class, 'values'], message: 'Prioridade inválida. Valores aceitos: low, medium, high, urgent.'),
            ],
            'startAt' => [
                new Assert\Type(type: 'string', message: 'A data de início deve ser um texto.'),
                new Assert\DateTime(format: \DATE_ATOM, message: 'A data de início deve estar no formato ISO 8601 (ex.: 2026-12-01T09:00:00-03:00).'),
            ],
            'dueAt' => [
                new Assert\Type(type: 'string', message: 'A data de vencimento deve ser um texto.'),
                new Assert\DateTime(format: \DATE_ATOM, message: 'A data de vencimento deve estar no formato ISO 8601 (ex.: 2026-12-01T18:00:00-03:00).'),
            ],
        ];

        foreach ($this->payload as $field => $value) {
            if (null === $value && \in_array($field, self::NULLABLE, true)) {
                continue; // null limpa o valor
            }

            $context->getValidator()->inContext($context)->atPath($field)->validate($value, $rules[$field]);
        }
    }

    /**
     * Só deve ser chamado depois de a validação passar.
     */
    public function toInput(int $taskId): UpdateTaskInput
    {
        $get = fn (string $field): mixed => $this->payload[$field] ?? null;

        return new UpdateTaskInput(
            taskId: $taskId,
            provided: array_values(array_map(static fn (string $field): string => self::FIELDS[$field], array_keys($this->payload))),
            title: isset($this->payload['title']) ? (string) $this->payload['title'] : null,
            description: null === $get('description') ? null : (string) $get('description'),
            priority: null === $get('priority') ? null : TaskPriority::from((string) $get('priority')),
            startDate: null === $get('startAt') ? null : new \DateTimeImmutable((string) $get('startAt')),
            dueDate: null === $get('dueAt') ? null : new \DateTimeImmutable((string) $get('dueAt')),
        );
    }
}
