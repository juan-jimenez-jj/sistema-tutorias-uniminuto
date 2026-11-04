<?php

declare(strict_types=1);

namespace Tutorias\Booking\Domain;

/**
 * Parámetros de negocio configurables (vienen de variables de entorno).
 * Centralizarlos aquí evita "números mágicos" dispersos como en el sistema heredado.
 */
final readonly class BookingPolicy
{
    public function __construct(
        public int $minAdvanceMinutes = 60,
        public int $maxDaysAhead = 28,
        public int $cancellationWindowHours = 3,
        public int $maxActivePerStudent = 5,
    ) {
    }
}
