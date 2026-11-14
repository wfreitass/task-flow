<?php

namespace App\Shared\Infrastructure\Email;

use App\Shared\Application\DTO\RenderedEmail;
use App\Shared\Application\Port\EmailTemplateRendererInterface;
use Twig\Environment;
use Twig\Error\Error as TwigError;

/**
 * Implementa a porta EmailTemplateRendererInterface com o Twig. Cada e-mail tem dois arquivos em `templates/email/`:
 *
 *   <nome>.html.twig   versão HTML; estende `layout.html.twig` e define o bloco `subject` (o assunto)
 *   <nome>.txt.twig    versão em texto puro, para clientes sem HTML
 *
 * O escape de HTML é automático nos .html.twig e desligado nos .txt.twig (o Twig decide pela extensão), então
 * um título como `<script>` aparece como texto no HTML e sem alteração no texto puro.
 */
final class TwigEmailTemplateRenderer implements EmailTemplateRendererInterface
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function render(string $template, array $context): RenderedEmail
    {
        if (1 !== preg_match('/^[a-z0-9_]+$/', $template)) {
            throw new \InvalidArgumentException(sprintf('Nome de modelo de e-mail inválido: "%s".', $template));
        }

        try {
            $html = $this->twig->load(sprintf('email/%s.html.twig', $template));

            // O assunto sai do bloco `subject`. O Twig o escapa para HTML (afinal, ele também é o <title>); como
            // o cabeçalho do e-mail é texto puro, desfazemos o escape ("A &amp; B" volta a ser "A & B").
            $subject = html_entity_decode($html->renderBlock('subject', $context), \ENT_QUOTES | \ENT_HTML5, 'UTF-8');

            return new RenderedEmail(
                trim((string) preg_replace('/\s+/', ' ', $subject)),
                $html->render($context),
                trim($this->twig->render(sprintf('email/%s.txt.twig', $template), $context))."\n",
            );
        } catch (TwigError $e) {
            throw new \RuntimeException(sprintf('Não foi possível gerar o e-mail "%s": %s', $template, $e->getMessage()), 0, $e);
        }
    }
}
