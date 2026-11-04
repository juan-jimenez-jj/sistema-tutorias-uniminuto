<?php

declare(strict_types=1);

namespace Tutorias\Scheduling\Application;

interface AvailabilityQueries
{
    /** @return list<array<string, mixed>> franjas del docente con nombre de materia y conteo de reservas futuras */
    public function slotsOfTeacher(int $teacherId, \DateTimeImmutable $now): array;

    /** @return list<array{id: int, codigo: string, nombre: string, area: string}> materias activas asignadas al docente */
    public function subjectsOfTeacher(int $teacherId): array;
}
