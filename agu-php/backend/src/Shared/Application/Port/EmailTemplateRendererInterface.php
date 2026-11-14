<?php

namespace App\Shared\Application\Port;

use App\Shared\Application\DTO\RenderedEmail;

/**
 * Gera o assunto e os corpos (HTML e texto) de um e-mail a partir de um modelo e dos dados. A aplicação diz QUAL
 * modelo (ex.: "task_assigned") e entrega dados simples; o layout, os textos e a formatação ficam no modelo,
 * na infraestrutura (hoje, Twig em `templates/email/`).
 */
interface EmailTemplateRendererInterface
{
    /**
     * @param string               $template nome do modelo, sem extensão (letras minúsculas, números e "_")
     * @param array<string, mixed> $context  dados do modelo: valores simples, sem HTML
     *
     * @throws \RuntimeException se o modelo não existir ou não puder ser gerado
     */
    public function render(string $template, array $context): RenderedEmail;
}
