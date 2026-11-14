<?php

namespace App\User\Presentation\Http\Request;

use App\User\Application\DTO\CreateProfileInput;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Contrato HTTP de POST /api/profiles ({"code", "name", "description"?, "roles"?: ["task.create", ...]}).
 */
final class CreateProfileRequest
{
    #[Assert\NotBlank(message: 'O código é obrigatório.')]
    #[Assert\Type(type: 'string', message: 'O código deve ser um texto.')]
    public mixed $code = null;

    #[Assert\NotBlank(message: 'O nome é obrigatório.')]
    #[Assert\Type(type: 'string', message: 'O nome deve ser um texto.')]
    #[Assert\Length(max: 100, maxMessage: 'O nome deve ter no máximo {{ limit }} caracteres.')]
    public mixed $name = null;

    #[Assert\Type(type: 'string', message: 'A descrição deve ser um texto.')]
    #[Assert\Length(max: 255, maxMessage: 'A descrição deve ter no máximo {{ limit }} caracteres.')]
    public mixed $description = null;

    public mixed $roles = null;

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $request = new self();
        $request->code = $payload['code'] ?? null;
        $request->name = $payload['name'] ?? null;
        $request->description = $payload['description'] ?? null;
        $request->roles = $payload['roles'] ?? null;

        return $request;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        RolesField::validate($context, $this->roles);
    }

    /**
     * Só deve ser chamado depois de a validação passar.
     */
    public function toInput(): CreateProfileInput
    {
        return new CreateProfileInput((string) $this->code, (string) $this->name, null === $this->description ? null : (string) $this->description, RolesField::toRoles($this->roles) ?? []);
    }
}
