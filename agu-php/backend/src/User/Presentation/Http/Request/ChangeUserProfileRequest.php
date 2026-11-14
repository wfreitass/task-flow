<?php

namespace App\User\Presentation\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contrato HTTP de PATCH /api/users/{id}/profile ({"profileId": 2}).
 */
final class ChangeUserProfileRequest
{
    #[Assert\NotNull(message: 'O perfil é obrigatório.')]
    #[Assert\Type(type: 'int', message: 'O perfil deve ser o id de um perfil (número inteiro).')]
    #[Assert\Positive(message: 'O perfil deve ser o id de um perfil (número inteiro positivo).')]
    public mixed $profileId = null;

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $request = new self();
        $request->profileId = $payload['profileId'] ?? null;

        return $request;
    }

    public function profileId(): int
    {
        return (int) $this->profileId;
    }
}
