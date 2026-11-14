<?php

namespace App\Tests\Integration\Task;

use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Tests\Support\TestUsers;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Testes simples contra o MySQL de testes: gravar, buscar e editar uma tarefa de verdade.
 * Cada teste roda dentro de uma transação que é desfeita no fim, então não deixa lixo no banco.
 */
final class TaskDatabaseTest extends KernelTestCase
{
    private Connection $db;
    private TaskRepositoryInterface $tasks;
    private int $userId;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->db = self::getContainer()->get(Connection::class);
        $this->tasks = self::getContainer()->get(TaskRepositoryInterface::class);

        $this->db->beginTransaction();
        $this->userId = (int) TestUsers::create(self::getContainer())->id();
    }

    protected function tearDown(): void
    {
        if ($this->db->isTransactionActive()) {
            $this->db->rollBack();
        }
        parent::tearDown();
    }

    public function testSaveAndFindATask(): void
    {
        // Gravar
        $task = $this->newTask('Estudar Doctrine');
        $this->tasks->add($task);

        // Buscar um item
        $found = $this->tasks->findAccessibleById((int) $task->id(), $this->userId);

        // Verificar o título, no objeto e na linha da tabela
        self::assertNotNull($found);
        self::assertSame('Estudar Doctrine', $found->title());
        self::assertSame('Estudar Doctrine', $this->db->fetchOne('SELECT title FROM tasks WHERE id = ?', [$task->id()]));
    }

    public function testEditATaskAndSaveIt(): void
    {
        $task = $this->newTask('Título antigo');
        $this->tasks->add($task);

        // Editar e gravar
        $task->changeDetails('Título novo', null, TaskPriority::Medium, null, null, new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $this->tasks->save($task);

        // O banco tem o título novo
        self::assertSame('Título novo', $this->db->fetchOne('SELECT title FROM tasks WHERE id = ?', [$task->id()]));
    }

    public function testAnotherUserCannotFindTheTask(): void
    {
        $task = $this->newTask('Privada');
        $this->tasks->add($task);
        $stranger = (int) TestUsers::create(self::getContainer(), 'estranho')->id();

        self::assertNull($this->tasks->findAccessibleById((int) $task->id(), $stranger));
    }

    private function newTask(string $title): Task
    {
        return Task::create($this->userId, $title, null, TaskPriority::Medium, null, null, null, new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
    }
}
