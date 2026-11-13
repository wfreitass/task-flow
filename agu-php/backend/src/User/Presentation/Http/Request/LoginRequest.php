<?php

namespace App\User\Presentation\Http\Request;

use App\User\Application\DTO\AuthenticateUserInput;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contrato HTTP de POST /api/login (o mesmo do frontend: {"username", "password"}).
 */
final class LoginRequest
{
    #[Assert\NotBlank(message: 'O nome de usuário é obrigatório.')]
    #[Assert\Type(type: 'string', message: 'O nome de usuário deve ser um texto.')]
    #[Assert\Length(max: 255)]
    public mixed $username = null;

    #[Assert\NotBlank(message: 'A senha é obrigatória.')]
    #[Assert\Type(type: 'string', message: 'A senha deve ser um texto.')]
    #[Assert\Length(max: 255)]
    public mixed $password = null;

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $request = new self();
        $request->username = $payload['username'] ?? null;
        $request->password = $payload['password'] ?? null;

        return $request;
    }

    /**
     * Só deve ser chamado depois de a validação passar.
     */
    public function toInput(): AuthenticateUserInput
    {
        return new AuthenticateUserInput((string) $this->username, (string) $this->password);
    }
}
