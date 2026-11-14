<?php

namespace App\Task\Infrastructure\Search;

use App\Shared\Domain\SortDirection;
use App\Shared\Infrastructure\Search\OpenSearchClient;
use App\Shared\Infrastructure\Search\OpenSearchException;
use App\Task\Application\DTO\TaskSearchResult;
use App\Task\Application\Port\TaskSearchIndexInterface;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskScope;
use App\Task\Domain\Enum\TaskSortField;
use App\Task\Domain\Repository\TaskCriteria;
use App\Task\Infrastructure\Persistence\Doctrine\Type\TaskStatusType;

/**
 * Implementa o índice de busca de tarefas no OpenSearch.
 *
 * O índice guarda só o necessário para PESQUISAR e FILTRAR (título, descrição, status, prioridade, quem criou e quem
 * é o responsável, datas). A busca devolve ids; os dados vêm do banco. Por isso um documento um pouco atrasado
 * nunca mostra informação errada, no máximo uma tarefa aparece ou some da busca por alguns instantes.
 *
 * Por que é melhor que o LIKE do MySQL: ignora acentos e maiúsculas ("relatorio" acha "Relatório"), entende
 * português (radicais: "entregas" acha "entrega"; palavras vazias como "de" e "para" não pesam), tolera erro de
 * digitação ("relatorop"), acha pelo começo da palavra ("rel"), dá mais peso ao título e ordena por relevância.
 */
final class OpenSearchTaskIndex implements TaskSearchIndexInterface
{
    /** O OpenSearch não pagina além disto (index.max_result_window). Passando, a busca cai para o MySQL. */
    private const MAX_RESULT_WINDOW = 10000;

    public function __construct(
        private readonly OpenSearchClient $client,
        private readonly string $indexName,
    ) {
    }

    public function index(Task $task): void
    {
        $this->withIndex(function () use ($task): void {
            // refresh=wait_for: a chamada só volta quando o documento já aparece nas buscas.
            $this->client->request('PUT', sprintf('%s/_doc/%d?refresh=wait_for', $this->indexName, $task->id()), $this->document($task));
        });
    }

    public function remove(int $taskId): void
    {
        $this->client->request('DELETE', sprintf('%s/_doc/%d?refresh=wait_for', $this->indexName, $taskId), null, [404]);
    }

    public function indexMany(array $tasks): void
    {
        if ([] === $tasks) {
            return;
        }

        $lines = [];
        foreach ($tasks as $task) {
            $lines[] = ['index' => ['_index' => $this->indexName, '_id' => (string) $task->id()]];
            $lines[] = $this->document($task);
        }

        $result = $this->withIndex(fn (): array => $this->client->bulk('_bulk?refresh=wait_for', $lines));
        if (true === ($result['body']['errors'] ?? false)) {
            throw new OpenSearchException('O OpenSearch rejeitou parte das tarefas na indexação em lote.');
        }
    }

    public function search(TaskCriteria $criteria): TaskSearchResult
    {
        if ($criteria->offset() + $criteria->perPage > self::MAX_RESULT_WINDOW) {
            throw new OpenSearchException('Página além do limite do índice de busca.');
        }

        $response = $this->client->request('POST', $this->indexName.'/_search', [
            'from' => $criteria->offset(),
            'size' => $criteria->perPage,
            'track_total_hits' => true,
            '_source' => false,
            'query' => $this->query($criteria),
            'sort' => $this->sort($criteria),
        ]);

        $hits = $response['body']['hits'] ?? [];
        $ids = array_map(static fn (array $hit): int => (int) $hit['_id'], $hits['hits'] ?? []);
        $total = $hits['total']['value'] ?? 0;

        return new TaskSearchResult($ids, (int) $total);
    }

    public function exists(): bool
    {
        return 200 === $this->client->request('HEAD', $this->indexName, null, [404])['status'];
    }

    public function ensure(bool $recreate = false): void
    {
        if ($recreate) {
            $this->client->request('DELETE', $this->indexName, null, [404]);
        } elseif ($this->exists()) {
            return;
        }

        // 400 resource_already_exists: outro processo criou entre a conferência e agora; está tudo certo.
        $this->client->request('PUT', $this->indexName, TaskIndexDefinition::definition(), [400]);
    }

    /**
     * Executa a operação; se o índice ainda não existir (ex.: primeira tarefa criada), cria e tenta de novo.
     *
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    private function withIndex(callable $operation): mixed
    {
        try {
            return $operation();
        } catch (OpenSearchException $e) {
            if (!str_contains($e->getMessage(), 'index_not_found_exception')) {
                throw $e;
            }
            $this->ensure();

            return $operation();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function document(Task $task): array
    {
        return [
            'id' => $task->id(),
            'title' => $task->title(),
            'description' => $task->description(),
            'status' => $task->status()->value,
            'statusOrder' => TaskStatusType::idOf($task->status()),
            'priority' => $task->priority()->value,
            'createdBy' => $task->createdBy(),
            'assignedTo' => $task->assignedTo(),
            'dueDate' => $task->dueDate()?->setTimezone(new \DateTimeZone('UTC'))->format(\DATE_ATOM),
            'createdAt' => $task->createdAt()->setTimezone(new \DateTimeZone('UTC'))->format(\DATE_ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function query(TaskCriteria $criteria): array
    {
        $filter = [match ($criteria->scope) {
            TaskScope::All => ['bool' => ['should' => [
                ['term' => ['createdBy' => $criteria->viewerId]],
                ['term' => ['assignedTo' => $criteria->viewerId]],
            ], 'minimum_should_match' => 1]],
            TaskScope::Created => ['term' => ['createdBy' => $criteria->viewerId]],
            TaskScope::Assigned => ['term' => ['assignedTo' => $criteria->viewerId]],
        }];

        if ([] !== $criteria->statuses) {
            $filter[] = ['terms' => ['status' => array_map(static fn ($status): string => $status->value, $criteria->statuses)]];
        }
        if ([] !== $criteria->priorities) {
            $filter[] = ['terms' => ['priority' => array_map(static fn ($priority): string => $priority->value, $criteria->priorities)]];
        }
        if (null !== $criteria->dueFrom || null !== $criteria->dueTo) {
            $range = [];
            if (null !== $criteria->dueFrom) {
                $range['gte'] = $criteria->dueFrom->format(\DATE_ATOM);
            }
            if (null !== $criteria->dueTo) {
                $range['lte'] = $criteria->dueTo->format(\DATE_ATOM);
            }
            $filter[] = ['range' => ['dueDate' => $range]];
        }

        return ['bool' => [
            'must' => [['bool' => ['should' => [
                // Palavras inteiras, com tolerância a erro de digitação; o título pesa mais que a descrição.
                ['multi_match' => [
                    'query' => (string) $criteria->search,
                    'fields' => ['title^3', 'description'],
                    'operator' => 'and',
                    'fuzziness' => 'AUTO',
                    'prefix_length' => 1,
                ]],
                // Começo de palavra no título ("rel" acha "Relatório").
                ['match' => ['title.prefix' => ['query' => (string) $criteria->search, 'operator' => 'and', 'boost' => 1.5]]],
            ], 'minimum_should_match' => 1]]],
            'filter' => $filter,
        ]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sort(TaskCriteria $criteria): array
    {
        $direction = SortDirection::Asc === $criteria->direction ? 'asc' : 'desc';

        $primary = match ($criteria->sortBy) {
            TaskSortField::Relevance => ['_score' => $direction],
            TaskSortField::CreatedAt => ['createdAt' => $direction],
            // Tarefas sem vencimento ficam sempre no fim, em qualquer direção (como no banco).
            TaskSortField::DueDate => ['dueDate' => ['order' => $direction, 'missing' => '_last']],
            TaskSortField::Title => ['title.sort' => $direction],
            TaskSortField::Status => ['statusOrder' => $direction],
        };

        // O id desempata, para que a paginação seja estável.
        return [$primary, ['id' => $direction]];
    }
}
