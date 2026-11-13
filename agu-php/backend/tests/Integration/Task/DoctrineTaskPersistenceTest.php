<?php

namespace App\Tests\Integration\Task;

use App\Shared\Application\Port\TransactionRunnerInterface;
use App\Task\Application\DTO\CreateTaskInput;
use App\Task\Application\Service\CreateTaskService;
use App\Task\Domain\Enum\TaskPriority;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Exercita mapeamento Doctrine, tipo task_status, repositórios e a transação contra o MySQL real.
 * Cada teste roda dentro de uma transação desfeita no tearDown.
 */
final class DoctrineTaskPersistenceTest extends KernelTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection = self::getContainer()->get(Connection::class);
        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }
        parent::tearDown();
    }

    public function testCreatesTaskWithBacklogStatusIdAndHistoryInDatabase(): void
    {
        $userId = $this->createUser('persist_'.bin2hex(random_bytes(4)));

        $output = self::getContainer()->get(CreateTaskService::class)->execute(
            new CreateTaskInput('Estudar Doctrine', 'detalhes', TaskPriority::Urgent, null, new \DateTimeImmutable('2026-12-01T18:00:00-03:00')),
            $userId,
        );

        $row = $this->connection->fetchAssociative('SELECT * FROM tasks WHERE id = ?', [$output->id]);
        self::assertIsArray($row);
        self::assertSame($userId, (int) $row['created_by']);
        self::assertSame('Estudar Doctrine', $row['title']);
        self::assertSame('urgent', $row['priority']);
        self::assertSame(1, (int) $row['status_id'], 'backlog deve ser gravado como id 1 do catálogo');
        self::assertSame('2026-12-01 21:00:00', $row['due_date'], 'datas são gravadas em UTC');
        self::assertNull($row['completed_at']);
        self::assertNull($row['deleted_at']);

        $history = $this->connection->fetchAllAssociative('SELECT * FROM task_history WHERE task_id = ?', [$output->id]);
        self::assertCount(1, $history);
        self::assertSame('created', $history[0]['action']);
        self::assertSame($userId, (int) $history[0]['actor_id']);
        $snapshot = json_decode((string) $history[0]['new_value'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('Estudar Doctrine', $snapshot['title']);
        self::assertSame('backlog', $snapshot['status']);
    }

    public function testRollsBackWhenTheWorkFails(): void
    {
        $userId = $this->createUser('rollback_'.bin2hex(random_bytes(4)));
        $before = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tasks WHERE created_by = ?', [$userId]);

        try {
            self::getContainer()->get(TransactionRunnerInterface::class)->run(function () use ($userId): void {
                $this->connection->executeStatement(
                    "INSERT INTO tasks (created_by, title) VALUES (?, 'não deve persistir')",
                    [$userId],
                );
                throw new \RuntimeException('falha simulada');
            });
            self::fail('Era esperada RuntimeException.');
        } catch (\RuntimeException) {
        }

        self::assertSame($before, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tasks WHERE created_by = ?', [$userId]));
    }

    private function createUser(string $username): int
    {
        $user = User::register($username, $username.'@example.com', 'hash-not-used', new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        self::getContainer()->get(UserRepositoryInterface::class)->add($user);

        return (int) $user->id();
    }
}
