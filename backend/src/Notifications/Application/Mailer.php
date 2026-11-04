<?php

declare(strict_types=1);

namespace Tutorias\Notifications\Application;

interface Mailer
{
    public function send(string $toEmail, string $toName, string $subject, string $html, string $text): void;
}
