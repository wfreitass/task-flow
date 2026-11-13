<?php

namespace App\Task\Application\DTO;

/**
 * Quem é um usuário e como contatá-lo. Só para notificações: nunca vai para respostas da API.
 */
final class UserContact
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
    ) {
    }
}
