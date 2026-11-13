<?php

namespace App\User\Presentation\Http\Request;

use App\User\Application\DTO\AuthenticateUserInput;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contrato HTTP de POST /api/login ({"email", "password"}).
 */
final class LoginRequest
{
    #[Assert\NotBlank(message: 'O e-mail é obrigatório.')]
    #[Assert\Type(type: 'string', message: 'O e-mail deve ser um texto.')]
    #[Assert\Email(message: 'O e-mail informado é inválido.')]
    #[Assert\Length(max: 180, maxMessage: 'O e-mail deve ter no máximo {{ limit }} caracteres.')]
    public mixed $email = null;

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
        $request->email = $payload['email'] ?? null;
        $request->password = $payload['password'] ?? null;

        return $request;
    }

    /**
     * Só deve ser chamado depois de a validação passar.
     */
    public function toInput(): AuthenticateUserInput
    {
        return new AuthenticateUserInput((string) $this->email, (string) $this->password);
    }
}
