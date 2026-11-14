<?php

namespace App\Tests\Unit\Task\Application;

use App\Task\Application\DTO\CreateTaskInput;
use App\Task\Application\DTO\UpdateTaskInput;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Tests\Support\TaskServices;
use PHPUnit\Framework\TestCase;

/**
 * Testes simples dos casos de uso: gravar, buscar e editar uma tarefa. Rodam em memória (sem banco).
 */
final class TaskServicesTest extends TestCase
{
    private const OWNER = 42;
    private const STRANGER = 99;

    private TaskServices $app;

    protected function setUp(): void
    {
        $this->app = TaskServices::inMemory();
    }

    public function testSaveAndFindATask(): void
    {
        // Gravar
        $created = $this->app->create->execute(new CreateTaskInput('Estudar Symfony', 'detalhes', TaskPriority::High, null, null), self::OWNER);

        // Buscar um item
        $found = $this->app->get->execute($created->id, self::OWNER);

        // Verificar o título
        self::assertSame($created->id, $found->id);
        self::assertSame('Estudar Symfony', $found->title);
    }

    public function testEditATask(): void
    {
        $created = $this->app->create->execute(new CreateTaskInput('Título antigo', null, TaskPriority::Medium, null, null), self::OWNER);

        // Editar: só o título é enviado, o resto não muda.
        $this->app->update->execute(new UpdateTaskInput($created->id, ['title'], title: 'Título novo'), self::OWNER);

        $found = $this->app->get->execute($created->id, self::OWNER);
        self::assertSame('Título novo', $found->title);
        self::assertSame(TaskPriority::Medium, $found->priority);
    }

    public function testFindingATaskThatDoesNotExistFails(): void
    {
        $this->expectException(TaskNotFoundException::class);

        $this->app->get->execute(12345, self::OWNER);
    }

    public function testSomeoneWithNoRelationToTheTaskCannotFindIt(): void
    {
        $created = $this->app->create->execute(new CreateTaskInput('Privada', null, TaskPriority::Low, null, null), self::OWNER);

        // Quem não criou nem é o responsável recebe "não encontrada", sem saber que ela existe.
        $this->expectException(TaskNotFoundException::class);

        $this->app->get->execute($created->id, self::STRANGER);
    }
}
