<?php

namespace App\Tests\Support;

use App\Shared\Application\DTO\IncomingFile;
use App\Shared\Application\DTO\StoredFile;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\EventPublisherInterface;
use App\Shared\Application\Port\FileStorageInterface;
use App\Shared\Application\Port\MimeTypeDetectorInterface;
use App\Shared\Application\Port\RoleCheckerInterface;
use App\Shared\Application\Port\TransactionRunnerInterface;
use App\Shared\Domain\Role;
use App\Task\Application\DTO\DirectoryUser;
use App\Task\Application\Port\UserDirectoryInterface;
use App\Task\Application\Service\CreateTaskService;
use App\Task\Application\Service\GetTaskService;
use App\Task\Application\Service\TaskOutputFactory;
use App\Task\Application\Service\UpdateTaskService;

/**
 * Monta os serviços de tarefa (criar, buscar, editar) com "dublês" simples no lugar do banco, do relógio, do
 * e-mail e do RabbitMQ. Serve aos testes unitários: rápidos, sem Docker e sem MySQL.
 *
 * Uso:  $app = TaskServices::inMemory();  $app->create->execute(...);  $app->get->execute(...);
 */
final class TaskServices
{
    private function __construct(
        public readonly CreateTaskService $create,
        public readonly GetTaskService $get,
        public readonly UpdateTaskService $update,
    ) {
    }

    public static function inMemory(): self
    {
        $tasks = new InMemoryTaskRepository();
        $history = new InMemoryTaskHistoryRepository();

        // Hora fixa: o teste não depende do relógio da máquina.
        $clock = new class implements ClockInterface {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-11-12 12:00:00', new \DateTimeZone('UTC'));
            }
        };

        // Sem banco, "transação" é só executar o trabalho.
        $transactions = new class implements TransactionRunnerInterface {
            public function run(callable $work): mixed
            {
                return $work();
            }
        };

        // Ninguém recebe e-mail nem evento nestes testes.
        $events = new class implements EventPublisherInterface {
            public function publish(object $event): void
            {
            }
        };

        // Todo usuário existe, está ativo e tem todas as roles.
        $roles = new class implements RoleCheckerInterface {
            public function assertHas(int $userId, Role $role): void
            {
            }
        };

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

        // Arquivos não são usados aqui, mas o serviço de criar os exige.
        $storage = new class implements FileStorageInterface {
            public function store(IncomingFile $file, string $mimeType): StoredFile
            {
                throw new \LogicException('Estes testes não enviam arquivos.');
            }

            public function delete(string $storageKey): void
            {
            }
        };
        $mimeTypes = new class implements MimeTypeDetectorInterface {
            public function detect(string $path): string
            {
                return 'application/octet-stream';
            }
        };

        $outputs = new TaskOutputFactory($directory);

        return new self(
            new CreateTaskService($tasks, $history, $clock, $transactions, new InMemoryTaskAttachmentRepository(), $storage, $mimeTypes, $directory, $outputs, $events, $roles),
            new GetTaskService($tasks, $outputs),
            new UpdateTaskService($tasks, $history, $clock, $transactions, $directory, $outputs, $events),
        );
    }
}
