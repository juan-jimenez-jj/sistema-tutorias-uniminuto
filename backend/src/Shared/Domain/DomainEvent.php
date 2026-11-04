<?php

declare(strict_types=1);

namespace Tutorias\Shared\Domain;

interface DomainEvent
{
    /** Nombre estable del evento, p. ej. "tutoria.reservada". */
    public function eventName(): string;

    /** @return array<string, mixed> */
    public function payload(): array;

    public function occurredAt(): \DateTimeImmutable;
}
