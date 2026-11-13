<?php

namespace App\Tests\Unit\Task\Application;

use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\TransactionRunnerInterface;
use App\Task\Application\DTO\CreateTaskInput;
use App\Task\Application\Service\CreateTaskService;
use App\Task\Domain\Enum\TaskHistoryAction;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Exception\InvalidTaskException;
use App\Tests\Support\InMemoryTaskHistoryRepository;
use App\Tests\Support\InMemoryTaskRepository;
use PHPUnit\Framework\TestCase;

final class CreateTaskServiceTest extends TestCase
{
    private InMemoryTaskRepository $tasks;
    private InMemoryTaskHistoryRepository $history;
    private CreateTaskService $service;
    private object $transactions;

    protected function setUp(): void
    {
        $this->tasks = new InMemoryTaskRepository();
        $this->history = new InMemoryTaskHistoryRepository();

        $clock = new class implements ClockInterface {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-11-12 12:00:00', new \DateTimeZone('UTC'));
            }
        };

        $this->transactions = new class implements TransactionRunnerInterface {
            public int $runs = 0;

            public function run(callable $work): mixed
            {
                ++$this->runs;

                return $work();
            }
        };

        $this->service = new CreateTaskService($this->tasks, $this->history, $clock, $this->transactions);
    }

    public function testCreatesTaskForAuthenticatedUserAndRecordsHistory(): void
    {
        $output = $this->service->execute(
            new CreateTaskInput('Estudar Symfony', 'detalhes', TaskPriority::High, null),
            42,
        );

        self::assertSame(1, $output->id);
        self::assertSame(42, $output->createdBy);
        self::assertSame(TaskStatus::Backlog, $output->status);
        self::assertSame(TaskPriority::High, $output->priority);

        self::assertCount(1, $this->tasks->tasks);
        self::assertCount(1, $this->history->entries);
        $entry = $this->history->entries[0];
        self::assertSame(TaskHistoryAction::Created, $entry->action());
        self::assertSame(42, $entry->actorId());
        self::assertSame('Estudar Symfony', $entry->newValue()['title'] ?? null);
    }

    public function testRunsEverythingInsideOneTransaction(): void
    {
        $this->service->execute(new CreateTaskInput('Título', null, TaskPriority::Medium, null), 1);

        self::assertSame(1, $this->transactions->runs);
    }

    public function testDomainRuleViolationPersistsNothing(): void
    {
        try {
            $this->service->execute(new CreateTaskInput('   ', null, TaskPriority::Medium, null), 1);
            self::fail('Era esperada InvalidTaskException.');
        } catch (InvalidTaskException) {
        }

        self::assertCount(0, $this->tasks->tasks);
        self::assertCount(0, $this->history->entries);
    }
}
