<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure;

use Tutorias\Shared\Domain\Clock;

final class SystemClock implements Clock
{
    private readonly \DateTimeZone $timezone;

    public function __construct(string $timezone = 'America/Bogota')
    {
        $this->timezone = new \DateTimeZone($timezone);
    }

    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', $this->timezone);
    }
}
