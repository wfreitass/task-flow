<?php

namespace App\Task\Infrastructure\Cache;

use App\Shared\Application\Port\CacheInterface;
use App\Shared\Infrastructure\Persistence\AfterCommitActions;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Repository\TaskCriteria;
use App\Task\Domain\Repository\TaskPage;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Decorator do repositório de tarefas que guarda no cache o resultado da listagem.
 *
 * Toda escrita de tarefa passa por add() ou save() (criar, editar, atribuir, mudar status, excluir), então é aqui
 * que o cache é invalidado: nenhum caso de uso precisa lembrar disso, e um caso de uso novo já nasce coberto.
 * A invalidação ocorre depois do commit (AfterCommitActions) e é por tag de usuário.
 *
 * Uma tarefa aparece na listagem de dois usuários (quem criou e o responsável), então uma escrita invalida a
 * listagem de até três: quem criou, o responsável atual e o responsável anterior, que deixa de ver a tarefa
 * (e cujas páginas se deslocam). O responsável anterior é lembrado de quando a tarefa foi carregada, o que vale
 * porque todo caso de uso carrega a tarefa antes de gravá-la.
 *
 * Só a listagem sem busca por texto é cacheada. A leitura de uma tarefa (findAccessibleById) vai sempre ao banco, que é a fonte de verdade.
 */
final class CachingTaskRepository implements TaskRepositoryInterface, ResetInterface
{
    /** Mude ao alterar o formato dos dados guardados: entradas antigas deixam de ser usadas. */
    private const SCHEMA = 'v2';

    /** @var array<int, int|null> responsável de cada tarefa no momento em que foi carregada */
    private array $assigneeWhenLoaded = [];

    public function __construct(
        private readonly TaskRepositoryInterface $source,
        private readonly CacheInterface $cache,
        private readonly AfterCommitActions $afterCommit,
        private readonly TaskPageCacheMapper $mapper,
        private readonly int $listTtlSeconds,
    ) {
    }

    public function add(Task $task): void
    {
        $this->source->add($task);
        $this->assigneeWhenLoaded[(int) $task->id()] = $task->assignedTo();
        $this->invalidateViewers([$task->createdBy(), $task->assignedTo()]);
    }

    public function save(Task $task): void
    {
        $previousAssignee = $this->assigneeWhenLoaded[(int) $task->id()] ?? null;

        $this->source->save($task);

        $this->assigneeWhenLoaded[(int) $task->id()] = $task->assignedTo();
        $this->invalidateViewers([$task->createdBy(), $task->assignedTo(), $previousAssignee]);
    }

    public function findAccessibleById(int $id, int $userId): ?Task
    {
        $task = $this->source->findAccessibleById($id, $userId);
        if (null !== $task) {
            $this->assigneeWhenLoaded[$id] = $task->assignedTo();
        }

        return $task;
    }

    public function findById(int $id): ?Task
    {
        return $this->source->findById($id);
    }

    public function findAccessibleByIds(array $ids, int $userId): array
    {
        return $this->source->findAccessibleByIds($ids, $userId);
    }

    public function findActiveBatchAfterId(int $afterId, int $limit): array
    {
        return $this->source->findActiveBatchAfterId($afterId, $limit);
    }

    public function search(TaskCriteria $criteria): TaskPage
    {
        // Busca por texto não é cacheada: vem do índice de busca, que é atualizado depois do commit (de forma
        // assíncrona), e um resultado guardado agora poderia ficar sem a alteração que acabou de ser feita.
        if (null !== $criteria->search && '' !== $criteria->search) {
            return $this->source->search($criteria);
        }

        $data = $this->cache->remember(
            $this->listKey($criteria),
            [self::viewerTag($criteria->viewerId)],
            fn (): array => $this->mapper->toArray($this->source->search($criteria)),
            $this->listTtlSeconds,
        );

        try {
            return $this->mapper->fromArray($data);
        } catch (\Throwable) {
            // Entrada em formato inesperado: ignora o cache e consulta o banco.
            return $this->source->search($criteria);
        }
    }

    public function reset(): void
    {
        $this->assigneeWhenLoaded = [];
    }

    /**
     * @param list<int|null> $userIds
     */
    private function invalidateViewers(array $userIds): void
    {
        $tags = array_values(array_unique(array_map(
            static fn (int $id): string => self::viewerTag($id),
            array_filter($userIds, static fn (?int $id): bool => null !== $id),
        )));

        $this->afterCommit->execute(fn () => $this->cache->invalidateTags($tags));
    }

    private static function viewerTag(int $userId): string
    {
        return 'tasks_user_'.$userId;
    }

    /**
     * Uma chave por combinação de usuário, escopo, filtros, ordenação e página.
     */
    private function listKey(TaskCriteria $criteria): string
    {
        $statuses = array_map(static fn ($status): string => $status->value, $criteria->statuses);
        $priorities = array_map(static fn ($priority): string => $priority->value, $criteria->priorities);
        sort($statuses);
        sort($priorities);

        return 'tasks_list_'.self::SCHEMA.'_'.hash('sha256', json_encode([
            $criteria->viewerId,
            $criteria->scope->value,
            $statuses,
            $priorities,
            $criteria->search,
            $criteria->dueFrom?->getTimestamp(),
            $criteria->dueTo?->getTimestamp(),
            $criteria->sortBy->value,
            $criteria->direction->value,
            $criteria->page,
            $criteria->perPage,
        ], \JSON_THROW_ON_ERROR));
    }
}
