<?php

declare(strict_types=1);

namespace Tutorias\Scheduling\Domain;

use Tutorias\Shared\Domain\ValidationError;

/** Franja semanal recurrente en la que un docente atiende tutorías de una materia. */
final class AvailabilitySlot
{
    public const MIN_DURATION = 30;
    public const MAX_DURATION = 240;
    public const EARLIEST = 6 * 60;   // 06:00
    public const LATEST = 22 * 60;    // 22:00

    private function __construct(
        private ?int $id,
        private readonly int $teacherId,
        private int $subjectId,
        private Weekday $weekday,
        private TimeRange $time,
        private string $location,
        private Modality $modality,
        private int $capacity,
        private bool $active,
    ) {
    }

    public static function publish(int $teacherId, int $subjectId, Weekday $weekday, TimeRange $time, string $location, Modality $modality, int $capacity): self
    {
        $slot = new self(null, $teacherId, $subjectId, $weekday, $time, '', $modality, 1, true);
        $slot->reschedule($subjectId, $weekday, $time, $location, $modality, $capacity);

        return $slot;
    }

    public static function fromPersistence(int $id, int $teacherId, int $subjectId, Weekday $weekday, TimeRange $time, string $location, Modality $modality, int $capacity, bool $active): self
    {
        return new self($id, $teacherId, $subjectId, $weekday, $time, $location, $modality, $capacity, $active);
    }

    public function reschedule(int $subjectId, Weekday $weekday, TimeRange $time, string $location, Modality $modality, int $capacity): void
    {
        if ($time->durationMinutes() < self::MIN_DURATION || $time->durationMinutes() > self::MAX_DURATION) {
            throw ValidationError::field('horaFin', sprintf('La franja debe durar entre %d y %d minutos.', self::MIN_DURATION, self::MAX_DURATION));
        }
        if ($time->startMinute < self::EARLIEST || $time->endMinute > self::LATEST) {
            throw ValidationError::field('horaInicio', 'Las tutorías se programan entre las 06:00 y las 22:00.');
        }
        if ($capacity < 1 || $capacity > 30) {
            throw ValidationError::field('cupo', 'El cupo debe estar entre 1 y 30 estudiantes.');
        }
        if (trim($location) === '') {
            throw ValidationError::field('lugar', 'Indica el salón o el enlace de la reunión.');
        }
        $this->subjectId = $subjectId;
        $this->weekday = $weekday;
        $this->time = $time;
        $this->location = trim($location);
        $this->modality = $modality;
        $this->capacity = $capacity;
    }

    public function changesSchedule(int $subjectId, Weekday $weekday, TimeRange $time): bool
    {
        return $subjectId !== $this->subjectId || $weekday !== $this->weekday
            || $time->startMinute !== $this->time->startMinute || $time->endMinute !== $this->time->endMinute;
    }

    public function conflictsWith(self $other): bool
    {
        return $other->id !== $this->id && $other->active && $this->active
            && $other->weekday === $this->weekday && $this->time->overlaps($other->time);
    }

    public function activate(): void
    { $this->active = true; }
    public function deactivate(): void
    { $this->active = false; }
    public function assignId(int $id): void
    { $this->id = $id; }

    public function id(): ?int
    { return $this->id; }
    public function teacherId(): int
    { return $this->teacherId; }
    public function subjectId(): int
    { return $this->subjectId; }
    public function weekday(): Weekday
    { return $this->weekday; }
    public function time(): TimeRange
    { return $this->time; }
    public function location(): string
    { return $this->location; }
    public function modality(): Modality
    { return $this->modality; }
    public function capacity(): int
    { return $this->capacity; }
    public function isActive(): bool
    { return $this->active; }
}
