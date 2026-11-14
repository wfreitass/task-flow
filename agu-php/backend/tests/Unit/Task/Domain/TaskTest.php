<?php

namespace App\Tests\Unit\Task\Domain;

use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Exception\InvalidTaskException;
use PHPUnit\Framework\TestCase;

/**
 * Testes simples da entidade Task: título, status inicial e edição. Não usam banco nem framework.
 */
final class TaskTest extends TestCase
{
    private const USER_ID = 1;

    public function testNewTaskKeepsItsTitleAndStartsInBacklog(): void
    {
        $task = $this->newTask('Estudar Symfony');

        self::assertSame('Estudar Symfony', $task->title());          // verificar o título
        self::assertSame(TaskStatus::Backlog, $task->status());        // toda tarefa nova começa no backlog
        self::assertSame(self::USER_ID, $task->createdBy());
    }

    public function testTitleIsTrimmed(): void
    {
        $task = $this->newTask('   Estudar Symfony   ');

        self::assertSame('Estudar Symfony', $task->title());
    }

    public function testEmptyTitleIsRejected(): void
    {
        $this->expectException(InvalidTaskException::class);

        $this->newTask('   ');
    }

    public function testEditingChangesTheTitle(): void
    {
        $task = $this->newTask('Título antigo');

        // Editar: troca o título e devolve o que mudou (para o histórico).
        $changes = $task->changeDetails('Título novo', null, TaskPriority::Medium, null, null, $this->now());

        self::assertSame('Título novo', $task->title());
        self::assertSame(['title' => 'Título antigo'], $changes['old']);
        self::assertSame(['title' => 'Título novo'], $changes['new']);
    }

    private function newTask(string $title): Task
    {
        return Task::create(self::USER_ID, $title, null, TaskPriority::Medium, null, null, null, $this->now());
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-11-12 12:00:00', new \DateTimeZone('UTC'));
    }
}
