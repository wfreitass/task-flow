<?php

namespace App\Shared\Application\Port;

use App\Shared\Application\DTO\EmailMessage;

/**
 * Envio de e-mail. A aplicação só conhece esta interface; o protocolo e o servidor (SMTP, Mailpit, um serviço
 * de e-mail) são detalhe da infraestrutura. Implementada em Shared/Infrastructure/Email.
 */
interface EmailSenderInterface
{
    /**
     * Envia o e-mail. Se o envio falhar, lança exceção: quem chama (em geral, o consumidor da fila) decide se
     * tenta de novo.
     */
    public function send(EmailMessage $message): void;
}
