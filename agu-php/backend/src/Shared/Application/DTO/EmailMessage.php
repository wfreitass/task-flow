<?php

namespace App\Shared\Application\DTO;

/**
 * Um e-mail a enviar, em dados simples. O remetente não entra aqui: é configuração da infraestrutura.
 */
final class EmailMessage
{
    public function __construct(
        public readonly string $toAddress,
        public readonly string $toName,
        public readonly string $subject,
        public readonly string $textBody,
        public readonly ?string $htmlBody = null,
    ) {
    }
}
