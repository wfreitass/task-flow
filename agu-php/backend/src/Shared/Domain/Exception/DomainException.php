<?php

namespace App\Shared\Domain\Exception;

/**
 * Violação de regra de negócio. O código de erro é estável e segue para a resposta HTTP.
 */
class DomainException extends \DomainException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        private readonly string $errorCode,
        string $message,
        private readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return $this->details;
    }
}
