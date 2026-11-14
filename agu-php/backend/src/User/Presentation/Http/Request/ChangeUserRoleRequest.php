<?php

namespace App\User\Presentation\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contrato HTTP do endpoint antigo PATCH /api/users/{id}/role ({"role": "<código do perfil>"}), mantido por
 * compatibilidade com o frontend atual. O substituto é PATCH /api/users/{id}/profile ({"profileId"}).
 */
final class ChangeUserRoleRequest
{
    #[Assert\NotBlank(message: 'O perfil é obrigatório.')]
    #[Assert\Type(type: 'string', message: 'O perfil deve ser um texto.')]
    #[Assert\Length(max: 50, maxMessage: 'O código do perfil deve ter no máximo {{ limit }} caracteres.')]
    public mixed $role = null;

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $request = new self();
        $request->role = $payload['role'] ?? null;

        return $request;
    }

    public function profileCode(): string
    {
        return (string) $this->role;
    }
}
