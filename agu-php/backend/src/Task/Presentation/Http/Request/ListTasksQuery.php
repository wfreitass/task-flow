<?php

namespace App\Task\Presentation\Http\Request;

use App\Shared\Domain\SortDirection;
use App\Task\Application\DTO\ListTasksInput;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskSortField;
use App\Task\Domain\Enum\TaskStatus;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Parâmetros de GET /api/tasks (query string): paginação, filtros e ordenação.
 * Todos opcionais. `status` e `priority` aceitam vários valores separados por vírgula.
 */
final class ListTasksQuery
{
    #[Assert\Type(type: 'string', message: 'A página deve ser um número inteiro.')]
    #[Assert\Regex(pattern: '/^[1-9]\d*$/', message: 'A página deve ser um inteiro a partir de 1.')]
    public mixed $page = null;

    #[Assert\Type(type: 'string', message: 'O tamanho da página deve ser um número inteiro.')]
    #[Assert\Regex(pattern: '/^([1-9]\d?|100)$/', message: 'O tamanho da página deve estar entre 1 e 100.')]
    public mixed $perPage = null;

    public mixed $status = null;

    public mixed $priority = null;

    #[Assert\Type(type: 'string', message: 'A busca deve ser um texto.')]
    #[Assert\Length(max: 100, maxMessage: 'A busca deve ter no máximo {{ limit }} caracteres.')]
    public mixed $q = null;

    #[Assert\Type(type: 'string', message: 'O início do intervalo de vencimento deve ser um texto.')]
    #[Assert\DateTime(format: \DATE_ATOM, message: 'dueFrom deve estar no formato ISO 8601 (ex.: 2026-12-01T00:00:00Z).')]
    public mixed $dueFrom = null;

    #[Assert\Type(type: 'string', message: 'O fim do intervalo de vencimento deve ser um texto.')]
    #[Assert\DateTime(format: \DATE_ATOM, message: 'dueTo deve estar no formato ISO 8601 (ex.: 2026-12-31T23:59:59Z).')]
    public mixed $dueTo = null;

    #[Assert\Type(type: 'string', message: 'A ordenação deve ser um texto.')]
    public mixed $sort = null;

    #[Assert\Type(type: 'string', message: 'O sentido da ordenação deve ser um texto.')]
    public mixed $order = null;

    /**
     * @param array<mixed> $query
     */
    public static function fromArray(array $query): self
    {
        $request = new self();
        foreach (['page', 'perPage', 'status', 'priority', 'q', 'dueFrom', 'dueTo', 'sort', 'order'] as $field) {
            $request->$field = $query[$field] ?? null;
        }

        return $request;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        $this->validateList($context, 'status', $this->status, TaskStatus::values());
        $this->validateList($context, 'priority', $this->priority, TaskPriority::values());

        if (null !== $this->sort && \is_string($this->sort) && !\in_array($this->sort, TaskSortField::values(), true)) {
            $context->buildViolation('Ordenação inválida. Valores aceitos: '.implode(', ', TaskSortField::values()).'.')->atPath('sort')->addViolation();
        }
        if (null !== $this->order && \is_string($this->order) && !\in_array($this->order, ['asc', 'desc'], true)) {
            $context->buildViolation('Sentido inválido. Valores aceitos: asc, desc.')->atPath('order')->addViolation();
        }
    }

    /**
     * Só deve ser chamado depois de a validação passar.
     */
    public function toInput(): ListTasksInput
    {
        return new ListTasksInput(
            statuses: array_map(static fn (string $value): TaskStatus => TaskStatus::from($value), self::split($this->status)),
            priorities: array_map(static fn (string $value): TaskPriority => TaskPriority::from($value), self::split($this->priority)),
            search: null === $this->q || '' === trim((string) $this->q) ? null : trim((string) $this->q),
            dueFrom: null === $this->dueFrom ? null : new \DateTimeImmutable((string) $this->dueFrom),
            dueTo: null === $this->dueTo ? null : new \DateTimeImmutable((string) $this->dueTo),
            sortBy: null === $this->sort ? TaskSortField::CreatedAt : TaskSortField::from((string) $this->sort),
            direction: null === $this->order ? SortDirection::Desc : SortDirection::from((string) $this->order),
            page: null === $this->page ? 1 : (int) $this->page,
            perPage: null === $this->perPage ? 20 : (int) $this->perPage,
        );
    }

    /**
     * @param list<string> $allowed
     */
    private function validateList(ExecutionContextInterface $context, string $field, mixed $value, array $allowed): void
    {
        if (null === $value) {
            return;
        }
        if (!\is_string($value)) {
            $context->buildViolation('O filtro deve ser um texto, com valores separados por vírgula.')->atPath($field)->addViolation();

            return;
        }

        foreach (self::split($value) as $item) {
            if (!\in_array($item, $allowed, true)) {
                $context->buildViolation(sprintf('Valor inválido "%s". Valores aceitos: %s.', $item, implode(', ', $allowed)))->atPath($field)->addViolation();

                return;
            }
        }
    }

    /**
     * @return list<string>
     */
    private static function split(mixed $value): array
    {
        if (!\is_string($value) || '' === trim($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('trim', explode(',', $value)), static fn (string $item): bool => '' !== $item)));
    }
}
