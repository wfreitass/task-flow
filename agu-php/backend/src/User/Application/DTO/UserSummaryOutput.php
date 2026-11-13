<?php

namespace App\User\Application\DTO;

use App\User\Domain\Entity\User;

/**
 * O que qualquer usuário autenticado pode saber de outro: quem ele é. Sem e-mail, senha ou papéis.
 */
final class UserSummaryOutput
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self((int) $user->id(), $user->username());
    }
}
