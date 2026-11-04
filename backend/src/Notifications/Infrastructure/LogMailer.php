<?php

declare(strict_types=1);

namespace Tutorias\Notifications\Infrastructure;

use Psr\Log\LoggerInterface;
use Tutorias\Notifications\Application\Mailer;

/** Mailer de desarrollo/pruebas: registra el correo en el log en lugar de enviarlo. */
final class LogMailer implements Mailer
{
    /** @var list<array{to: string, subject: string}> */
    public array $sent = [];

    public function __construct(private readonly ?LoggerInterface $logger = null)
    {
    }

    public function send(string $toEmail, string $toName, string $subject, string $html, string $text): void
    {
        $this->sent[] = ['to' => $toEmail, 'subject' => $subject];
        $this->logger?->info('mail.simulado', ['para' => $toEmail, 'asunto' => $subject]);
    }
}
