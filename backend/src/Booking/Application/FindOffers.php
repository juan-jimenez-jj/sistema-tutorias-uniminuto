<?php

declare(strict_types=1);

namespace Tutorias\Booking\Application;

use Tutorias\Booking\Domain\BookingPolicy;
use Tutorias\Shared\Application\Actor;
use Tutorias\Shared\Domain\Clock;

/**
 * Calcula el calendario de cupos disponibles de las próximas semanas para una materia.
 * Reemplaza los endpoints AJAX heredados (cargar_semanas_disponibles.php,
 * cargar_horarios_fecha.php…) que devolvían HTML y consultaban una columna inexistente.
 */
final class FindOffers
{
    public function __construct(
        private readonly OfferQueries $queries,
        private readonly Clock $clock,
        private readonly BookingPolicy $policy,
    ) {
    }

    /** @return array{desde: string, hasta: string, dias: list<array<string, mixed>>} */
    public function execute(Actor $student, int $subjectId, ?int $teacherId): array
    {
        $now = $this->clock->now();
        $from = $now->setTime(0, 0);
        $to = $from->modify("+{$this->policy->maxDaysAhead} days");
        $earliest = $now->modify("+{$this->policy->minAdvanceMinutes} minutes");

        $slots = $this->queries->bookableSlots($subjectId, $teacherId);
        $taken = $this->queries->takenSeats(array_column($slots, 'id'), $from, $to);
        $mine = $this->queries->studentBookings($student->id, $from, $to);

        $byWeekday = [];
        foreach ($slots as $slot) {
            $byWeekday[$slot['diaSemana']][] = $slot;
        }

        $days = [];
        for ($date = $from; $date <= $to; $date = $date->modify('+1 day')) {
            $weekday = (int) $date->format('N');
            $offers = [];
            foreach ($byWeekday[$weekday] ?? [] as $slot) {
                [$h, $m] = array_map('intval', explode(':', $slot['horaInicio']));
                if ($date->setTime($h, $m) < $earliest) {
                    continue;
                }
                $key = $slot['id'] . '|' . $date->format('Y-m-d');
                $free = max(0, $slot['cupo'] - ($taken[$key] ?? 0));
                $offers[] = $slot + [
                    'cuposDisponibles' => $free,
                    'yaReservada' => isset($mine[$key]),
                ];
            }
            if ($offers !== []) {
                $days[] = [
                    'fecha' => $date->format('Y-m-d'),
                    'diaSemana' => $weekday,
                    'cuposTotales' => array_sum(array_column($offers, 'cuposDisponibles')),
                    'franjas' => $offers,
                ];
            }
        }

        return ['desde' => $from->format('Y-m-d'), 'hasta' => $to->format('Y-m-d'), 'dias' => $days];
    }
}
