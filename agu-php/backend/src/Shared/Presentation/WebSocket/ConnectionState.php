<?php

namespace App\Shared\Presentation\WebSocket;

use React\EventLoop\TimerInterface;

/**
 * Estado de uma conexão WebSocket: de quem é (depois de autenticada) e os temporizadores que a fecham.
 */
final class ConnectionState
{
    public ?int $userId = null;
    public ?TimerInterface $timer = null;
    public ?TimerInterface $expiryTimer = null;
}
