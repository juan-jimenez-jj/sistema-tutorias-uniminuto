<?php

declare(strict_types=1);

namespace Tutorias\Notifications\Infrastructure;

use PHPMailer\PHPMailer\PHPMailer;
use Tutorias\Notifications\Application\Mailer;

final class SmtpMailer implements Mailer
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $username,
        private readonly string $password,
        private readonly string $encryption,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {
    }

    public function send(string $toEmail, string $toName, string $subject, string $html, string $text): void
    {
        $mail = new PHPMailer(true); // true = lanza excepciones → la outbox reintenta
        $mail->isSMTP();
        $mail->Host = $this->host;
        $mail->Port = $this->port;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->Timeout = 10;
        if ($this->username !== '') {
            $mail->SMTPAuth = true;
            $mail->Username = $this->username;
            $mail->Password = $this->password;
        }
        $mail->SMTPSecure = $this->encryption;
        $mail->SMTPAutoTLS = $this->encryption !== '';
        $mail->setFrom($this->fromEmail, $this->fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = $text;
        $mail->send();
    }
}
