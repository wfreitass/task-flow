<?php

namespace App\Shared\Infrastructure\Email;

use App\Shared\Application\DTO\EmailMessage;
use App\Shared\Application\Port\EmailSenderInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Implementa a porta EmailSenderInterface com o Symfony Mailer. O servidor vem de MAILER_DSN (Mailpit no
 * desenvolvimento, `null://` nos testes) e o remetente de MAIL_FROM.
 */
final class SymfonyMailerEmailSender implements EmailSenderInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $from,
    ) {
    }

    public function send(EmailMessage $message): void
    {
        $email = (new Email())
            ->from(Address::create($this->from))
            ->to(new Address($message->toAddress, $message->toName))
            ->subject($message->subject)
            ->text($message->textBody);

        if (null !== $message->htmlBody) {
            $email->html($message->htmlBody);
        }

        $this->mailer->send($email);
    }
}
