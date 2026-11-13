<?php

namespace App\Shared\Presentation\Http;

use App\Shared\Presentation\Http\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RequestValidator
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    /**
     * @throws ValidationFailedException se o Request DTO violar alguma constraint
     */
    public function validate(object $request): void
    {
        $violations = $this->validator->validate($request);
        if (\count($violations) > 0) {
            throw new ValidationFailedException($violations);
        }
    }
}
