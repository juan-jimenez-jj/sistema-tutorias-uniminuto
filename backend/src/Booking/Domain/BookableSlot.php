<?php

declare(strict_types=1);

namespace Tutorias\Booking\Domain;

/**
 * Vista de una franja desde el punto de vista de Reservas (modelo propio del módulo;
 * no reutiliza la entidad de Disponibilidad para mantener los módulos desacoplados).
 */
final readonly class BookableSlot
{
    public function __construct(
        public int $id,
        public int $teacherId,
        public string $teacherName,
        public int $subjectId,
        public string $subjectName,
        public int $weekday,
        public string $start,
        public string $end,
        public string $location,
        public string $modality,
        public int $capacity,
        public bool $active,
        public bool $teacherActive,
        public bool $subjectActive,
        public bool $teacherTeachesSubject,
    ) {
    }

    public function isBookable(): bool
    {
        return $this->active && $this->teacherActive && $this->subjectActive && $this->teacherTeachesSubject;
    }
}
