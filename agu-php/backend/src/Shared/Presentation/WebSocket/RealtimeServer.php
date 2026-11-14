<?php

namespace App\Shared\Presentation\WebSocket;

use App\Shared\Application\Port\AccessTokenVerifierInterface;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use React\EventLoop\LoopInterface;

/**
 * Servidor de WebSocket: mantém as conexões dos usuários e entrega a cada um as mensagens de tempo real.
 *
 * Protocolo (JSON, uma mensagem por quadro):
 *   cliente → {"type":"auth","token":"<JWT da API>"}   (primeira mensagem, em até AUTH_TIMEOUT segundos)
 *   servidor → {"type":"auth.ok","userId":1,"expiresAt":"..."}   ou   {"type":"error","code":"UNAUTHORIZED"} e fecha
 *   cliente → {"type":"ping"}   servidor → {"type":"pong"}
 *   servidor → {"type":"task.status_changed","id":"...","occurredAt":"...","data":{...}}
 *
 * O token vai na primeira mensagem, e não na URL, porque o navegador não permite cabeçalhos no WebSocket e uma
 * URL com token acaba em logs. A conexão é fechada quando o token expira (código 4401): o cliente renova o
 * token e reconecta.
 *
 * Idempotência: a mesma mensagem (mesmo id) nunca é entregue duas vezes por este servidor.
 */
final class RealtimeServer implements MessageComponentInterface
{
    public const AUTH_TIMEOUT = 5;
    public const MAX_CONNECTIONS_PER_USER = 10;
    private const MAX_CLIENT_MESSAGE_BYTES = 8192;

    private const CLOSE_MESSAGE_TOO_BIG = 1009;
    private const CLOSE_UNAUTHORIZED = 4401;
    private const CLOSE_TOO_MANY_CONNECTIONS = 4429;

    /** @var \SplObjectStorage<ConnectionInterface, ConnectionState> */
    private \SplObjectStorage $connections;

    /** @var array<int, array<int, ConnectionInterface>> userId => [spl_object_id => conexão] */
    private array $byUser = [];

    private RecentIds $delivered;

    public function __construct(
        private readonly AccessTokenVerifierInterface $tokens,
        private readonly LoopInterface $loop,
        private readonly LoggerInterface $logger,
    ) {
        $this->connections = new \SplObjectStorage();
        $this->delivered = new RecentIds();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $state = new ConnectionState();
        // Quem não se identifica a tempo é desconectado, para não acumular conexões abertas e anônimas.
        $state->timer = $this->loop->addTimer(self::AUTH_TIMEOUT, function () use ($conn): void {
            $this->reject($conn, 'Autenticação não enviada a tempo.');
        });
        $this->connections->attach($conn, $state);
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        if (strlen((string) $msg) > self::MAX_CLIENT_MESSAGE_BYTES) {
            $from->close(self::CLOSE_MESSAGE_TOO_BIG);

            return;
        }
        $payload = json_decode((string) $msg, true);
        $type = is_array($payload) ? ($payload['type'] ?? null) : null;
        $state = $this->connections[$from] ?? null;
        if (null === $state) {
            return;
        }

        if (null === $state->userId) {
            if ('auth' !== $type || !is_string($payload['token'] ?? null)) {
                $this->reject($from, 'A primeira mensagem deve ser {"type":"auth","token":"..."}.');

                return;
            }
            $this->authenticate($from, $state, $payload['token']);

            return;
        }

        if ('ping' === $type) {
            $this->send($from, ['type' => 'pong']);
        }
        // Qualquer outra mensagem do cliente é ignorada: este canal só entrega eventos.
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $state = $this->connections[$conn] ?? null;
        if (null === $state) {
            return;
        }
        $this->cancelTimers($state);
        if (null !== $state->userId) {
            unset($this->byUser[$state->userId][spl_object_id($conn)]);
            if ([] === $this->byUser[$state->userId]) {
                unset($this->byUser[$state->userId]);
            }
        }
        $this->connections->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $this->logger->warning('Erro numa conexão WebSocket: '.$e->getMessage());
        $conn->close();
    }

    /**
     * Entrega a mensagem às conexões dos destinatários. Idempotente pelo `id`.
     *
     * @param list<int>            $recipientIds
     * @param array<string, mixed> $data
     *
     * @return array{duplicate: bool, delivered: int} quantas conexões receberam
     */
    public function deliver(string $id, string $type, string $occurredAt, array $recipientIds, array $data): array
    {
        $now = time();
        if ($this->delivered->has($id, $now)) {
            return ['duplicate' => true, 'delivered' => 0];
        }
        $this->delivered->remember($id, $now);

        $frame = json_encode(['type' => $type, 'id' => $id, 'occurredAt' => $occurredAt, 'data' => $data], \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
        $count = 0;
        foreach (array_unique($recipientIds) as $userId) {
            foreach ($this->byUser[$userId] ?? [] as $conn) {
                $conn->send($frame);
                ++$count;
            }
        }

        return ['duplicate' => false, 'delivered' => $count];
    }

    public function connectionCount(): int
    {
        return count($this->connections);
    }

    private function authenticate(ConnectionInterface $conn, ConnectionState $state, string $token): void
    {
        try {
            $identity = $this->tokens->verify($token);
        } catch (\Throwable $e) {
            $this->logger->error('Falha ao validar o token do WebSocket: '.$e->getMessage());
            $identity = null;
        }
        if (null === $identity || $identity->expiresAt <= new \DateTimeImmutable()) {
            $this->reject($conn, 'Token inválido ou expirado.');

            return;
        }
        if (count($this->byUser[$identity->userId] ?? []) >= self::MAX_CONNECTIONS_PER_USER) {
            $this->send($conn, ['type' => 'error', 'code' => 'TOO_MANY_CONNECTIONS', 'message' => 'Muitas conexões abertas para este usuário.']);
            $conn->close(self::CLOSE_TOO_MANY_CONNECTIONS);

            return;
        }

        $this->cancelTimers($state);
        $state->userId = $identity->userId;
        $this->byUser[$identity->userId][spl_object_id($conn)] = $conn;
        // O token vale até `exp`: depois disso a conexão cai, e o cliente precisa se autenticar de novo.
        $seconds = max(1, $identity->expiresAt->getTimestamp() - time());
        $state->expiryTimer = $this->loop->addTimer($seconds, function () use ($conn): void {
            $this->reject($conn, 'Token expirado.');
        });

        $this->send($conn, ['type' => 'auth.ok', 'userId' => $identity->userId, 'expiresAt' => $identity->expiresAt->format(\DATE_ATOM)]);
    }

    private function reject(ConnectionInterface $conn, string $message): void
    {
        $this->send($conn, ['type' => 'error', 'code' => 'UNAUTHORIZED', 'message' => $message]);
        $conn->close(self::CLOSE_UNAUTHORIZED);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function send(ConnectionInterface $conn, array $payload): void
    {
        $conn->send(json_encode($payload, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES));
    }

    private function cancelTimers(ConnectionState $state): void
    {
        foreach ([$state->timer, $state->expiryTimer] as $timer) {
            if (null !== $timer) {
                $this->loop->cancelTimer($timer);
            }
        }
        $state->timer = $state->expiryTimer = null;
    }
}
