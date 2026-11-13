<?php

namespace App\Tests\Functional\Task;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Security\SecurityUser;
use Doctrine\DBAL\Connection;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateTaskEndpointTest extends WebTestCase
{
    private const PASSWORD = 'senha-de-teste-123';

    private KernelBrowser $client;
    private int $userId;
    private string $username;
    private SecurityUser $securityUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->username = 'func_'.bin2hex(random_bytes(4));
        $container = static::getContainer();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        // Custo baixo apenas para acelerar os testes; o hasher "auto" do Symfony verifica hashes bcrypt normalmente.
        $hash = password_hash(self::PASSWORD, \PASSWORD_BCRYPT, ['cost' => 4]);

        $user = User::register($this->username, $this->username.'@example.com', $hash, $now);
        $container->get(UserRepositoryInterface::class)->add($user);
        $this->userId = (int) $user->id();
        $this->securityUser = new SecurityUser($user);
    }

    protected function tearDown(): void
    {
        // O kernel é reiniciado a cada requisição; por isso a limpeza é explícita, e não por rollback.
        /** @var Connection $db */
        $db = static::getContainer()->get(Connection::class);
        $db->executeStatement('DELETE FROM task_history WHERE actor_id = ?', [$this->userId]);
        $db->executeStatement('DELETE FROM tasks WHERE created_by = ?', [$this->userId]);
        $db->executeStatement('DELETE FROM users WHERE id = ?', [$this->userId]);
        parent::tearDown();
    }

    public function testLoginReturnsJwtThatCreatesATask(): void
    {
        $this->client->jsonRequest('POST', '/api/login', ['email' => $this->username.'@example.com', 'password' => self::PASSWORD]);
        self::assertResponseIsSuccessful();
        $token = $this->json()['token'] ?? null;
        self::assertIsString($token);

        $this->post(['title' => 'Estudar Symfony'], $token);

        self::assertResponseStatusCodeSame(201);
    }

    public function testCreatesTaskAndPersistsOwnerFromToken(): void
    {
        $this->post([
            'title' => 'Estudar Symfony',
            'description' => 'Implementar DTO, domínio e repositório',
            'priority' => 'high',
            'dueAt' => '2026-12-01T18:00:00-03:00',
        ]);

        self::assertResponseStatusCodeSame(201);
        $body = $this->json();
        self::assertSame('Estudar Symfony', $body['title']);
        self::assertSame('high', $body['priority']);
        self::assertSame('backlog', $body['status']);
        self::assertSame('2026-12-01T21:00:00+00:00', $body['dueAt']);
        self::assertIsInt($body['id']);

        /** @var Connection $db */
        $db = static::getContainer()->get(Connection::class);
        self::assertSame($this->userId, (int) $db->fetchOne('SELECT created_by FROM tasks WHERE id = ?', [$body['id']]));
        self::assertSame(1, (int) $db->fetchOne("SELECT COUNT(*) FROM task_history WHERE task_id = ? AND action = 'created'", [$body['id']]));
    }

    public function testIgnoresOwnerSentInBody(): void
    {
        $this->post(['title' => 'Tarefa', 'createdBy' => 999999, 'ownerId' => 999999]);

        self::assertResponseStatusCodeSame(201);
        /** @var Connection $db */
        $db = static::getContainer()->get(Connection::class);
        self::assertSame($this->userId, (int) $db->fetchOne('SELECT created_by FROM tasks WHERE id = ?', [$this->json()['id']]));
    }

    public function testDefaultsPriorityToMedium(): void
    {
        $this->post(['title' => 'Sem prioridade']);

        self::assertResponseStatusCodeSame(201);
        self::assertSame('medium', $this->json()['priority']);
    }

    public function testRequiresAuthentication(): void
    {
        $this->client->jsonRequest('POST', '/api/tasks', ['title' => 'Sem token']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testRejectsInvalidData(): void
    {
        $this->post(['title' => '', 'priority' => 'critical', 'dueAt' => 'amanhã']);

        self::assertResponseStatusCodeSame(422);
        $error = $this->json()['error'];
        self::assertSame('VALIDATION_FAILED', $error['code']);
        self::assertArrayHasKey('title', $error['details']);
        self::assertArrayHasKey('priority', $error['details']);
        self::assertArrayHasKey('dueAt', $error['details']);
    }

    public function testRejectsNonStringTitle(): void
    {
        $this->post(['title' => 123]);

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('title', $this->json()['error']['details']);
    }

    public function testRejectsMalformedJson(): void
    {
        $this->client->request('POST', '/api/tasks', server: $this->headers(), content: '{"title": ');

        self::assertResponseStatusCodeSame(400);
        self::assertSame('MALFORMED_REQUEST', $this->json()['error']['code']);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function post(array $payload, ?string $token = null): void
    {
        $this->client->request('POST', '/api/tasks', server: $this->headers($token), content: json_encode($payload, \JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, string>
     */
    private function headers(?string $token = null): array
    {
        $token ??= static::getContainer()->get(JWTTokenManagerInterface::class)->create($this->securityUser);

        return ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token];
    }

    /**
     * @return array<string, mixed>
     */
    private function json(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }
}
