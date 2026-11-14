<?php

namespace App\User\Presentation\Http\Request;

use App\User\Application\DTO\UpdateProfileInput;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Contrato HTTP de PATCH /api/profiles/{id}. Todos os campos são opcionais; o que não vier não muda.
 * `description: null` limpa a descrição. `roles` substitui o conjunto de roles do profile.
 */
final class UpdateProfileRequest
{
    #[Assert\Type(type: 'string', message: 'O nome deve ser um texto.')]
    #[Assert\Length(min: 1, max: 100, minMessage: 'O nome não pode ficar vazio.', maxMessage: 'O nome deve ter no máximo {{ limit }} caracteres.')]
    public mixed $name = null;

    #[Assert\Type(type: 'string', message: 'A descrição deve ser um texto.')]
    #[Assert\Length(max: 255, maxMessage: 'A descrição deve ter no máximo {{ limit }} caracteres.')]
    public mixed $description = null;

    public mixed $roles = null;

    private bool $hasDescription = false;

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $request = new self();
        $request->name = $payload['name'] ?? null;
        $request->description = $payload['description'] ?? null;
        $request->hasDescription = \array_key_exists('description', $payload);
        $request->roles = $payload['roles'] ?? null;

        return $request;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        RolesField::validate($context, $this->roles);
    }

    public function toInput(int $profileId): UpdateProfileInput
    {
        return new UpdateProfileInput(
            $profileId,
            null === $this->name ? null : (string) $this->name,
            $this->hasDescription,
            null === $this->description ? null : (string) $this->description,
            RolesField::toRoles($this->roles),
        );
    }
}
