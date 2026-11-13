<?php

namespace App\Tests\Unit\Task\Domain;

use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Exception\InvalidTaskException;
use PHPUnit\Framework\TestCase;

final class TaskTest extends TestCase
{
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new \DateTimeImmutable('2026-11-12 12:00:00', new \DateTimeZone('UTC'));
    }

    public function testCreatesTaskInBacklogWithNormalizedFields(): void
    {
        $task = Task::create(7, '  Estudar Symfony  ', "  detalhes \n", TaskPriority::High, null, null, null, $this->now);

        self::assertSame(7, $task->createdBy());
        self::assertSame('Estudar Symfony', $task->title());
        self::assertSame('detalhes', $task->description());
        self::assertSame(TaskPriority::High, $task->priority());
        self::assertSame(TaskStatus::Backlog, $task->status());
        self::assertNull($task->id());
        self::assertSame($this->now, $task->createdAt());
        self::assertSame($this->now, $task->updatedAt());
    }

    public function testBlankDescriptionBecomesNull(): void
    {
        $task = Task::create(1, 'Título', '   ', TaskPriority::Medium, null, null, null, $this->now);

        self::assertNull($task->description());
    }

    public function testDueDateIsConvertedToUtc(): void
    {
        $due = new \DateTimeImmutable('2026-12-01T18:00:00-03:00');

        $task = Task::create(1, 'Título', null, TaskPriority::Low, null, $due, null, $this->now);

        self::assertSame('2026-12-01T21:00:00+00:00', $task->dueDate()?->format(\DATE_ATOM));
    }

    public function testRejectsBlankTitle(): void
    {
        $this->expectException(InvalidTaskException::class);
        $this->expectExceptionMessage('obrigatório');

        Task::create(1, "   \t", null, TaskPriority::Medium, null, null, null, $this->now);
    }

    public function testRejectsTooLongTitle(): void
    {
        try {
            Task::create(1, str_repeat('a', 256), null, TaskPriority::Medium, null, null, null, $this->now);
            self::fail('Era esperada InvalidTaskException.');
        } catch (InvalidTaskException $e) {
            self::assertSame('TASK_TITLE_TOO_LONG', $e->errorCode());
        }
    }

    public function testAcceptsTitleWithExactlyMaxLength(): void
    {
        $task = Task::create(1, str_repeat('a', 255), null, TaskPriority::Medium, null, null, null, $this->now);

        self::assertSame(255, mb_strlen($task->title()));
    }

    public function testRejectsMissingCreator(): void
    {
        $this->expectException(InvalidTaskException::class);

        Task::create(0, 'Título', null, TaskPriority::Medium, null, null, null, $this->now);
    }

    public function testSnapshotContainsOnlyScalarValues(): void
    {
        $task = Task::create(1, 'Título', null, TaskPriority::Urgent, null, null, null, $this->now);

        self::assertSame(
            ['assigned_to' => null, 'title' => 'Título', 'description' => null, 'priority' => 'urgent', 'status' => 'backlog', 'start_date' => null, 'due_date' => null, 'completed_at' => null],
            $task->snapshot(),
        );
    }
}
