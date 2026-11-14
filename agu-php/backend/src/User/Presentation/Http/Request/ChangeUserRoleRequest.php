<?php

namespace App\User\Presentation\Http\Request;

use App\User\Domain\Enum\UserRole;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contrato HTTP de PATCH /api/users/{id}/role ({"role": "admin" | "executor"}).
 */
final class ChangeUserRoleRequest
{
    #[Assert\NotBlank(message: 'O perfil é obrigatório.')]
    #[Assert\Type(type: 'string', message: 'O perfil deve ser um texto.')]
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

    #[Assert\Callback]
    public function validate(\Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        if (\is_string($this->role) && '' !== $this->role && null === UserRole::tryFrom($this->role)) {
            $context->buildViolation('Perfil inválido. Valores aceitos: '.implode(', ', UserRole::values()).'.')->atPath('role')->addViolation();
        }
    }

    /**
     * Só deve ser chamado depois de a validação passar.
     */
    public function role(): UserRole
    {
        return UserRole::from((string) $this->role);
    }
}
