<?php

declare(strict_types=1);

namespace Tutorias\Booking\Domain\Event;

use Tutorias\Shared\Domain\DomainEvent;

abstract class TutoringEvent implements DomainEvent
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public readonly int $tutoringId,
        private readonly array $data,
        private readonly \DateTimeImmutable $occurredAt,
    ) {
    }

    public function payload(): array
    {
        return ['tutoriaId' => $this->tutoringId] + $this->data;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
