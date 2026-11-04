<?php

declare(strict_types=1);

namespace Tutorias\Scheduling\Application;

use Tutorias\Scheduling\Domain\AvailabilitySlot;

final class SlotView
{
    /** @return array<string, mixed> */
    public static function from(AvailabilitySlot $slot): array
    {
        return [
            'id' => $slot->id(),
            'docenteId' => $slot->teacherId(),
            'materiaId' => $slot->subjectId(),
            'diaSemana' => $slot->weekday()->value,
            'diaNombre' => $slot->weekday()->label(),
            'horaInicio' => $slot->time()->start(),
            'horaFin' => $slot->time()->end(),
            'duracionMinutos' => $slot->time()->durationMinutes(),
            'lugar' => $slot->location(),
            'modalidad' => $slot->modality()->value,
            'cupo' => $slot->capacity(),
            'activa' => $slot->isActive(),
        ];
    }
}
