<?php

declare(strict_types=1);

namespace Tutorias\Scheduling\Application;

/**
 * Puerto de lectura hacia el módulo de Reservas: Disponibilidad necesita saber si una
 * franja tiene reservas futuras antes de modificarla, sin depender de las clases de Booking.
 */
interface BookingsOfSlot
{
    public function futureScheduledCount(int $slotId, \DateTimeImmutable $now): int;

    /** Máximo de reservas programadas en una misma fecha futura para la franja. */
    public function maxFutureScheduledPerDate(int $slotId, \DateTimeImmutable $now): int;
}
