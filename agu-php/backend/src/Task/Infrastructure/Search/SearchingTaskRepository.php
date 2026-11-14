<?php

namespace App\Task\Infrastructure\Search;

use App\Shared\Application\Port\EventPublisherInterface;
use App\Shared\Application\Port\ClockInterface;
use App\Task\Application\Port\TaskSearchIndexInterface;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Event\TaskChanged;
use App\Task\Domain\Repository\TaskCriteria;
use App\Task\Domain\Repository\TaskPage;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Decorator do repositório de tarefas que liga o banco ao índice de busca (OpenSearch). Fica entre o cache e o
 * repositório Doctrine.
 *
 * ESCRITA: toda escrita de tarefa passa por add() ou save() (criar, editar, atribuir, mudar status, excluir), então
 * é aqui que se publica o TaskChanged, que o worker usa para atualizar o índice. Nenhum caso de uso precisa lembrar
 * disso. O evento só sai depois do commit (EventPublisherInterface).
 *
 * LEITURA: uma busca por texto (`search` preenchido) consulta o índice, que devolve os ids já ordenados; os dados
 * saem do banco, que reaplica o acesso do usuário. Sem texto, a listagem vai direto ao banco. Se o OpenSearch
 * estiver fora do ar, sem o índice ou sem resposta, a busca cai no LIKE do MySQL: menos esperta, mas funciona.
 */
final class SearchingTaskRepository implements TaskRepositoryInterface
{
    public function __construct(
        private readonly TaskRepositoryInterface $database,
        private readonly TaskSearchIndexInterface $index,
        private readonly EventPublisherInterface $events,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function add(Task $task): void
    {
        $this->database->add($task);
        $this->announce($task);
    }

    public function save(Task $task): void
    {
        $this->database->save($task);
        $this->announce($task);
    }

    public function findAccessibleById(int $id, int $userId): ?Task
    {
        return $this->database->findAccessibleById($id, $userId);
    }

    public function findById(int $id): ?Task
    {
        return $this->database->findById($id);
    }

    public function findAccessibleByIds(array $ids, int $userId): array
    {
        return $this->database->findAccessibleByIds($ids, $userId);
    }

    public function findActiveBatchAfterId(int $afterId, int $limit): array
    {
        return $this->database->findActiveBatchAfterId($afterId, $limit);
    }

    public function search(TaskCriteria $criteria): TaskPage
    {
        if (null === $criteria->search || '' === $criteria->search) {
            return $this->database->search($criteria);
        }

        try {
            $result = $this->index->search($criteria);
        } catch (\RuntimeException $e) {
            $this->logger->warning('Busca no OpenSearch falhou; usando o MySQL: '.$e->getMessage());

            return $this->database->search($criteria);
        }

        return new TaskPage($this->database->findAccessibleByIds($result->ids, $criteria->viewerId), $result->total);
    }

    private function announce(Task $task): void
    {
        $this->events->publish(new TaskChanged((int) $task->id(), $this->clock->now()->format('Y-m-d\TH:i:s.u\Z')));
    }
}
