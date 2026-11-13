<?php

namespace App\User\Presentation\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Parâmetros de GET /api/users. A busca exige ao menos 2 caracteres, para não virar uma listagem de todos.
 */
final class SearchUsersQuery
{
    #[Assert\NotBlank(message: 'Informe o texto da busca (parâmetro q).')]
    #[Assert\Type(type: 'string', message: 'A busca deve ser um texto.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'A busca deve ter no mínimo {{ limit }} caracteres.', maxMessage: 'A busca deve ter no máximo {{ limit }} caracteres.')]
    public mixed $q = null;

    /**
     * @param array<mixed> $query
     */
    public static function fromArray(array $query): self
    {
        $request = new self();
        $request->q = $query['q'] ?? null;

        return $request;
    }

    /**
     * Só deve ser chamado depois de a validação passar.
     */
    public function text(): string
    {
        return trim((string) $this->q);
    }
}
