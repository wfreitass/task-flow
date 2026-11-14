<?php

namespace App\Task\Application\Service;

use App\Task\Application\Port\TaskSearchIndexInterface;
use App\Task\Domain\Repository\TaskRepositoryInterface;

/**
 * Refaz o índice de busca a partir do banco: serve para a primeira carga, para depois de mudar o mapeamento e
 * para recuperar eventos de indexação perdidos (ex.: OpenSearch fora do ar).
 */
final class ReindexTasksService
{
    private const BATCH_SIZE = 200;

    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskSearchIndexInterface $index,
    ) {
    }

    /**
     * @param bool $recreate     apaga o índice antes (remove também o que já não existe no banco)
     * @param bool $onlyIfMissing não faz nada se o índice já existir
     *
     * @return int|null quantas tarefas foram indexadas; null se nada foi feito
     */
    public function execute(bool $recreate = false, bool $onlyIfMissing = false): ?int
    {
        if ($onlyIfMissing && !$recreate && $this->index->exists()) {
            return null;
        }

        $this->index->ensure($recreate);

        $count = 0;
        $afterId = 0;
        while ([] !== ($batch = $this->tasks->findActiveBatchAfterId($afterId, self::BATCH_SIZE))) {
            $this->index->indexMany($batch);
            $count += \count($batch);
            $afterId = (int) end($batch)->id();
        }

        return $count;
    }
}
