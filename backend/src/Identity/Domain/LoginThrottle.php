<?php

declare(strict_types=1);

namespace Tutorias\Identity\Domain;

interface LoginThrottle
{
    /** Segundos que debe esperar el cliente, o 0 si puede intentar. */
    public function secondsUntilAllowed(string $email, ?string $ip): int;

    public function registerAttempt(string $email, ?string $ip, bool $success): void;
}
