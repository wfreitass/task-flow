<?php

namespace App\Task\Infrastructure\Persistence\Doctrine;

use App\Shared\Domain\SortDirection;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskScope;
use App\Task\Domain\Enum\TaskSortField;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Repository\TaskCriteria;
use App\Task\Domain\Repository\TaskPage;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Infrastructure\Persistence\Doctrine\Entity\TaskEntity;
use App\Task\Infrastructure\Persistence\Doctrine\Mapper\TaskMapper;
use App\Task\Infrastructure\Persistence\Doctrine\Type\TaskStatusType;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final class DoctrineTaskRepository implements TaskRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TaskMapper $mapper,
    ) {
    }

    public function add(Task $task): void
    {
        $entity = $this->mapper->toEntity($task);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->mapper->syncGeneratedId($task, $entity);
    }

    public function save(Task $task): void
    {
        $entity = null === $task->id() ? null : $this->entityManager->find(TaskEntity::class, $task->id());
        if (null === $entity) {
            throw new \LogicException('Só é possível gravar uma tarefa que já foi criada.');
        }

        $this->mapper->fill($entity, $task);
        $this->entityManager->flush();
    }

    public function findAccessibleById(int $id, int $userId): ?Task
    {
        /** @var TaskEntity|null $entity */
        $entity = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(TaskEntity::class, 't')
            ->where('t.id = :id')
            ->andWhere('t.deletedAt IS NULL')
            ->andWhere('(t.createdBy = :user OR t.assignedTo = :user)')
            ->setParameter('id', $id)
            ->setParameter('user', $userId)
            ->getQuery()
            ->getOneOrNullResult();

        return null === $entity ? null : $this->mapper->toDomain($entity);
    }

    public function search(TaskCriteria $criteria): TaskPage
    {
        $total = (int) $this->filtered($criteria)->select('COUNT(t.id)')->getQuery()->getSingleScalarResult();

        $query = $this->filtered($criteria)->select('t');
        $this->applyOrder($query, $criteria);
        $entities = $query
            ->setFirstResult($criteria->offset())
            ->setMaxResults($criteria->perPage)
            ->getQuery()
            ->getResult();

        return new TaskPage(array_map(fn (TaskEntity $entity): Task => $this->mapper->toDomain($entity), $entities), $total);
    }

    /**
     * Base da busca: sempre só tarefas que o usuário enxerga (criou ou é o responsável, conforme o escopo)
     * e não excluídas, mais os filtros pedidos.
     */
    private function filtered(TaskCriteria $criteria): QueryBuilder
    {
        $visibility = match ($criteria->scope) {
            TaskScope::All => '(t.createdBy = :viewer OR t.assignedTo = :viewer)',
            TaskScope::Created => 't.createdBy = :viewer',
            TaskScope::Assigned => 't.assignedTo = :viewer',
        };

        $query = $this->entityManager->createQueryBuilder()
            ->from(TaskEntity::class, 't')
            ->where($visibility)
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('viewer', $criteria->viewerId);

        if ([] !== $criteria->statuses) {
            // Os ids do catálogo ficam na infraestrutura (TaskStatusType).
            $query->andWhere('t.status IN (:statuses)')->setParameter(
                'statuses',
                array_map(static fn (TaskStatus $status): int => TaskStatusType::idOf($status), $criteria->statuses),
                ArrayParameterType::INTEGER,
            );
        }
        if ([] !== $criteria->priorities) {
            $query->andWhere('t.priority IN (:priorities)')->setParameter(
                'priorities',
                array_map(static fn (TaskPriority $priority): string => $priority->value, $criteria->priorities),
                ArrayParameterType::STRING,
            );
        }
        if (null !== $criteria->search && '' !== $criteria->search) {
            // Escapa os curingas do LIKE para que a busca seja por texto literal.
            $query->andWhere('(t.title LIKE :search OR t.description LIKE :search)')
                ->setParameter('search', '%'.addcslashes($criteria->search, '%_\\').'%');
        }
        if (null !== $criteria->dueFrom) {
            $query->andWhere('t.dueDate >= :dueFrom')->setParameter('dueFrom', $criteria->dueFrom, Types::DATETIME_IMMUTABLE);
        }
        if (null !== $criteria->dueTo) {
            $query->andWhere('t.dueDate <= :dueTo')->setParameter('dueTo', $criteria->dueTo, Types::DATETIME_IMMUTABLE);
        }

        return $query;
    }

    private function applyOrder(QueryBuilder $query, TaskCriteria $criteria): void
    {
        $direction = SortDirection::Asc === $criteria->direction ? 'ASC' : 'DESC';

        if (TaskSortField::DueDate === $criteria->sortBy) {
            // Tarefas sem vencimento ficam sempre no fim, em qualquer direção.
            $query->addSelect('CASE WHEN t.dueDate IS NULL THEN 1 ELSE 0 END AS HIDDEN dueDateIsNull')
                ->addOrderBy('dueDateIsNull', 'ASC');
        }

        $field = match ($criteria->sortBy) {
            TaskSortField::CreatedAt => 't.createdAt',
            TaskSortField::DueDate => 't.dueDate',
            TaskSortField::Title => 't.title',
            TaskSortField::Status => 't.status',
        };

        // O id desempata, para que a paginação seja estável.
        $query->addOrderBy($field, $direction)->addOrderBy('t.id', $direction);
    }
}
