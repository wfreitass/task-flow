<?php

namespace App\Task\Infrastructure\Persistence\Doctrine\Mapper;

use App\Shared\Infrastructure\Persistence\DomainObjectHydrator;
use App\Task\Domain\Entity\Task;
use App\Task\Infrastructure\Persistence\Doctrine\Entity\TaskEntity;

/**
 * Converte a entidade de domínio Task e o modelo de persistência TaskEntity.
 */
final class TaskMapper
{
    public function __construct(private readonly DomainObjectHydrator $hydrator)
    {
    }

    /**
     * Cria o modelo de persistência de uma tarefa nova (sem id).
     */
    public function toEntity(Task $task): TaskEntity
    {
        $entity = new TaskEntity();
        $this->fill($entity, $task);

        return $entity;
    }

    /**
     * Copia o estado do domínio para o modelo de persistência (base também para futuras atualizações).
     */
    public function fill(TaskEntity $entity, Task $task): void
    {
        $entity->createdBy = $task->createdBy();
        $entity->title = $task->title();
        $entity->description = $task->description();
        $entity->priority = $task->priority();
        $entity->status = $task->status();
        $entity->startDate = $task->startDate();
        $entity->dueDate = $task->dueDate();
        $entity->completedAt = $task->completedAt();
        $entity->deletedAt = $task->deletedAt();
        $entity->createdAt = $task->createdAt();
        $entity->updatedAt = $task->updatedAt();
    }

    /**
     * Reconstrói a tarefa a partir do que está no banco, sem refazer as validações de criação.
     */
    public function toDomain(TaskEntity $entity): Task
    {
        return $this->hydrator->hydrate(Task::class, [
            'id' => $entity->id,
            'createdBy' => $entity->createdBy,
            'title' => $entity->title,
            'description' => $entity->description,
            'priority' => $entity->priority,
            'status' => $entity->status,
            'startDate' => $entity->startDate,
            'dueDate' => $entity->dueDate,
            'completedAt' => $entity->completedAt,
            'deletedAt' => $entity->deletedAt,
            'createdAt' => $entity->createdAt,
            'updatedAt' => $entity->updatedAt,
        ]);
    }

    /**
     * Depois do INSERT, devolve ao domínio o id que o banco gerou.
     */
    public function syncGeneratedId(Task $task, TaskEntity $entity): void
    {
        $this->hydrator->set($task, 'id', $entity->id);
    }
}
