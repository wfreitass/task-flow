<?php

namespace App\Tests\Functional\Task;

use App\Tests\Support\TestUsers;
use App\User\Infrastructure\Security\SecurityUser;
use Doctrine\DBAL\Connection;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Testes simples da API de ponta a ponta (HTTP + JWT + MySQL de testes): gravar, buscar e editar uma tarefa.
 */
final class TaskApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private int $userId;
    private string $token;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $user = TestUsers::create(static::getContainer());
        $this->userId = (int) $user->id();
        // O token JWT de quem "está logado" neste teste.
        $this->token = static::getContainer()->get(JWTTokenManagerInterface::class)->create(new SecurityUser($user));
    }

    protected function tearDown(): void
    {
        // O kernel é reiniciado a cada requisição, então a limpeza é explícita (não dá para usar rollback).
        /** @var Connection $db */
        $db = static::getContainer()->get(Connection::class);
        $db->executeStatement('DELETE FROM task_history WHERE actor_id = ?', [$this->userId]);
        $db->executeStatement('DELETE FROM tasks WHERE created_by = ?', [$this->userId]);
        $db->executeStatement('DELETE FROM users WHERE id = ?', [$this->userId]);
        parent::tearDown();
    }

    public function testCreateATask(): void
    {
        // Gravar
        $task = $this->call('POST', '/api/tasks', ['title' => 'Estudar Symfony'], 201);

        self::assertSame('Estudar Symfony', $task['title']);
        self::assertSame('backlog', $task['status']);
        self::assertIsInt($task['id']);
    }

    public function testFindATaskById(): void
    {
        $id = $this->call('POST', '/api/tasks', ['title' => 'Estudar Symfony'], 201)['id'];

        // Buscar um item
        $task = $this->call('GET', '/api/tasks/'.$id, [], 200);

        // Verificar o título
        self::assertSame($id, $task['id']);
        self::assertSame('Estudar Symfony', $task['title']);
    }

    public function testEditTheTitleOfATask(): void
    {
        $id = $this->call('POST', '/api/tasks', ['title' => 'Título antigo'], 201)['id'];

        // Editar
        $edited = $this->call('PATCH', '/api/tasks/'.$id, ['title' => 'Título novo'], 200);
        self::assertSame('Título novo', $edited['title']);

        // E ao buscar de novo, o título novo está gravado.
        self::assertSame('Título novo', $this->call('GET', '/api/tasks/'.$id, [], 200)['title']);
    }

    public function testFindingATaskThatDoesNotExistReturns404(): void
    {
        $error = $this->call('GET', '/api/tasks/999999999', [], 404);

        self::assertSame('TASK_NOT_FOUND', $error['error']['code']);
    }

    public function testRequestWithoutTokenIsRejected(): void
    {
        $this->client->request('GET', '/api/tasks');

        self::assertResponseStatusCodeSame(401);
    }

    /**
     * Faz a chamada autenticada, confere o status HTTP esperado e devolve o JSON da resposta.
     *
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    private function call(string $method, string $uri, array $body, int $expectedStatus): array
    {
        $this->client->request(
            $method,
            $uri,
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$this->token],
            content: [] === $body ? null : json_encode($body, \JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame($expectedStatus);

        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }
}
