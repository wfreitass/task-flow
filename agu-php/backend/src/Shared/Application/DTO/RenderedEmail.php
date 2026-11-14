<?php

namespace App\Shared\Application\DTO;

/**
 * O resultado de aplicar um modelo de e-mail aos dados: assunto e os dois corpos, prontos para enviar.
 */
final class RenderedEmail
{
    public function __construct(
        public readonly string $subject,
        public readonly string $html,
        public readonly string $text,
    ) {
    }
}
