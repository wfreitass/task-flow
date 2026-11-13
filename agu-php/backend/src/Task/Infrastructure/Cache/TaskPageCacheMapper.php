<?php

namespace App\Task\Infrastructure\Cache;

use App\Shared\Infrastructure\Persistence\DomainObjectHydrator;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Repository\TaskPage;

/**
 * Converte uma página de tarefas em dados simples (e de volta) para guardar no cache.
 * Não se serializa o objeto de domínio: o cache fica independente da estrutura interna da classe Task.
 */
final class TaskPageCacheMapper
{
    public function __construct(private readonly DomainObjectHydrator $hydrator)
    {
    }

    /**
     * @return array{total: int, items: list<array<string, mixed>>}
     */
    public function toArray(TaskPage $page): array
    {
        return [
            'total' => $page->total,
            'items' => array_map(fn (Task $task): array => [
                'id' => $task->id(),
                'createdBy' => $task->createdBy(),
                'assignedTo' => $task->assignedTo(),
                'title' => $task->title(),
                'description' => $task->description(),
                'priority' => $task->priority()->value,
                'status' => $task->status()->value,
                'startDate' => $this->date($task->startDate()),
                'dueDate' => $this->date($task->dueDate()),
                'completedAt' => $this->date($task->completedAt()),
                'deletedAt' => $this->date($task->deletedAt()),
                'createdAt' => $this->date($task->createdAt()),
                'updatedAt' => $this->date($task->updatedAt()),
            ], $page->tasks),
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws \Throwable se os dados não tiverem o formato esperado (o chamador trata como cache vazio)
     */
    public function fromArray(array $data): TaskPage
    {
        $tasks = array_map(fn (array $item): Task => $this->hydrator->hydrate(Task::class, [
            'id' => $item['id'],
            'createdBy' => $item['createdBy'],
            'assignedTo' => $item['assignedTo'],
            'title' => $item['title'],
            'description' => $item['description'],
            'priority' => TaskPriority::from($item['priority']),
            'status' => TaskStatus::from($item['status']),
            'startDate' => $this->parse($item['startDate']),
            'dueDate' => $this->parse($item['dueDate']),
            'completedAt' => $this->parse($item['completedAt']),
            'deletedAt' => $this->parse($item['deletedAt']),
            'createdAt' => $this->parse($item['createdAt']),
            'updatedAt' => $this->parse($item['updatedAt']),
        ]), $data['items']);

        return new TaskPage($tasks, (int) $data['total']);
    }

    private function date(?\DateTimeImmutable $date): ?string
    {
        return $date?->format(\DATE_ATOM);
    }

    private function parse(?string $date): ?\DateTimeImmutable
    {
        return null === $date ? null : new \DateTimeImmutable($date);
    }
}
