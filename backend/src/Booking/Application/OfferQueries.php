<?php

declare(strict_types=1);

namespace Tutorias\Booking\Application;

interface OfferQueries
{
    /**
     * Franjas reservables de una materia (opcionalmente de un docente).
     * @return list<array{id: int, docenteId: int, docenteNombre: string, diaSemana: int, horaInicio: string, horaFin: string, lugar: string, modalidad: string, cupo: int}>
     */
    public function bookableSlots(int $subjectId, ?int $teacherId): array;

    /**
     * Cupos ocupados por franja y fecha en un rango.
     * @param list<int> $slotIds
     * @return array<string, int> clave "franjaId|AAAA-MM-DD"
     */
    public function takenSeats(array $slotIds, \DateTimeImmutable $from, \DateTimeImmutable $to): array;

    /**
     * Franjas y fechas en las que el estudiante ya tiene reserva.
     * @return array<string, true> clave "franjaId|AAAA-MM-DD"
     */
    public function studentBookings(int $studentId, \DateTimeImmutable $from, \DateTimeImmutable $to): array;
}
