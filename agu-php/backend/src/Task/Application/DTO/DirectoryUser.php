<?php

namespace App\Task\Application\DTO;

/**
 * O que o contexto Task precisa saber de um usuário: quem ele é. Nada de e-mail, senha ou papéis.
 */
final class DirectoryUser
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
    ) {
    }
}
