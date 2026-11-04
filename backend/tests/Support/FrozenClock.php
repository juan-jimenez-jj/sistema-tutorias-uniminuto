<?php

declare(strict_types=1);

namespace Tutorias\Tests\Support;

use Tutorias\Shared\Domain\Clock;

final class FrozenClock implements Clock
{
    private \DateTimeImmutable $now;

    public function __construct(string $now = '2027-03-01 08:00:00')
    {
        $this->now = new \DateTimeImmutable($now, new \DateTimeZone('America/Bogota'));
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function travelTo(string $moment): void
    {
        $this->now = new \DateTimeImmutable($moment, new \DateTimeZone('America/Bogota'));
    }
}
