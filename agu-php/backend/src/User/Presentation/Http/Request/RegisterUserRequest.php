<?php

namespace App\User\Presentation\Http\Request;

use App\User\Application\DTO\RegisterUserInput;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contrato HTTP de POST /api/register. Valida o formato; unicidade e demais regras ficam na aplicação e no domínio.
 * Propriedades `mixed` permitem rejeitar tipos errados como erro de validação.
 */
final class RegisterUserRequest
{
    #[Assert\NotBlank(message: 'O nome de usuário é obrigatório.')]
    #[Assert\Type(type: 'string', message: 'O nome de usuário deve ser um texto.')]
    #[Assert\Length(min: 3, max: 50, minMessage: 'O nome de usuário deve ter no mínimo {{ limit }} caracteres.', maxMessage: 'O nome de usuário deve ter no máximo {{ limit }} caracteres.')]
    public mixed $username = null;

    #[Assert\NotBlank(message: 'O e-mail é obrigatório.')]
    #[Assert\Type(type: 'string', message: 'O e-mail deve ser um texto.')]
    #[Assert\Email(message: 'O e-mail informado é inválido.')]
    #[Assert\Length(max: 180, maxMessage: 'O e-mail deve ter no máximo {{ limit }} caracteres.')]
    public mixed $email = null;

    #[Assert\NotBlank(message: 'A senha é obrigatória.')]
    #[Assert\Type(type: 'string', message: 'A senha deve ser um texto.')]
    #[Assert\Length(min: 8, max: 72, minMessage: 'A senha deve ter no mínimo {{ limit }} caracteres.', maxMessage: 'A senha deve ter no máximo {{ limit }} caracteres.')]
    public mixed $password = null;

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $request = new self();
        $request->username = $payload['username'] ?? null;
        $request->email = $payload['email'] ?? null;
        $request->password = $payload['password'] ?? null;

        return $request;
    }

    /**
     * Só deve ser chamado depois de a validação passar.
     */
    public function toInput(): RegisterUserInput
    {
        return new RegisterUserInput((string) $this->username, (string) $this->email, (string) $this->password);
    }
}
