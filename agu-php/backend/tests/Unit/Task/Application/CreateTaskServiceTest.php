<?php

namespace App\Tests\Unit\Task\Application;

use App\Shared\Application\DTO\IncomingFile;
use App\Shared\Application\DTO\StoredFile;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\FileStorageInterface;
use App\Shared\Application\Port\MimeTypeDetectorInterface;
use App\Shared\Application\Port\TransactionRunnerInterface;
use App\Task\Application\DTO\CreateTaskInput;
use App\Task\Application\DTO\DirectoryUser;
use App\Task\Application\Port\UserDirectoryInterface;
use App\Task\Application\Service\CreateTaskService;
use App\Task\Application\Service\TaskOutputFactory;
use App\Task\Domain\Enum\TaskHistoryAction;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Exception\InvalidTaskException;
use App\Tests\Support\InMemoryTaskAttachmentRepository;
use App\Tests\Support\InMemoryTaskHistoryRepository;
use App\Tests\Support\InMemoryTaskRepository;
use PHPUnit\Framework\TestCase;

final class CreateTaskServiceTest extends TestCase
{
    private InMemoryTaskRepository $tasks;
    private InMemoryTaskHistoryRepository $history;
    private CreateTaskService $service;
    private object $transactions;
    private InMemoryTaskAttachmentRepository $attachments;
    private object $storage;

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

        $this->attachments = new InMemoryTaskAttachmentRepository();

        // Dublês: o conteúdo do arquivo não importa nestes testes, só o fluxo.
        $detector = new class implements MimeTypeDetectorInterface {
            public function detect(string $path): string
            {
                return 'application/pdf';
            }
        };
        $this->storage = new class implements FileStorageInterface {
            /** @var list<string> */
            public array $stored = [];
            /** @var list<string> */
            public array $deleted = [];

            public function store(IncomingFile $file, string $mimeType): StoredFile
            {
                $key = '2026/11/'.str_pad((string) (\count($this->stored) + 1), 32, '0', \STR_PAD_LEFT).'.pdf';
                $this->stored[] = $key;

                return new StoredFile($key, str_repeat('a', 64));
            }

            public function delete(string $storageKey): void
            {
                $this->deleted[] = $storageKey;
            }
        };

        // Dublê: todo id de usuário existe e está ativo.
        $directory = new class implements UserDirectoryInterface {
            public function findActiveById(int $id): ?DirectoryUser
            {
                return new DirectoryUser($id, 'usuario'.$id);
            }

            public function findByIds(array $ids): array
            {
                return array_combine($ids, array_map(static fn (int $id): DirectoryUser => new DirectoryUser($id, 'usuario'.$id), $ids)) ?: [];
            }
        };

        $this->service = new CreateTaskService($this->tasks, $this->history, $clock, $this->transactions, $this->attachments, $this->storage, $detector, $directory, new TaskOutputFactory($directory));
    }

    public function testCreatesTaskForAuthenticatedUserAndRecordsHistory(): void
    {
        $output = $this->service->execute(
            new CreateTaskInput('Estudar Symfony', 'detalhes', TaskPriority::High, null, null),
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
        $this->service->execute(new CreateTaskInput('Título', null, TaskPriority::Medium, null, null), 1);

        self::assertSame(1, $this->transactions->runs);
    }

    public function testDomainRuleViolationPersistsNothing(): void
    {
        try {
            $this->service->execute(new CreateTaskInput('   ', null, TaskPriority::Medium, null, null), 1);
            self::fail('Era esperada InvalidTaskException.');
        } catch (InvalidTaskException) {
        }

        self::assertCount(0, $this->tasks->tasks);
        self::assertCount(0, $this->history->entries);
    }
}
