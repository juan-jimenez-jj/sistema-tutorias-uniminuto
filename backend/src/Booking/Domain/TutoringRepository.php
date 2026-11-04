<?php

declare(strict_types=1);

namespace Tutorias\Booking\Domain;

interface TutoringRepository
{
    /** Obtiene la franja con bloqueo pesimista (SELECT … FOR UPDATE) para serializar reservas concurrentes. */
    public function lockBookableSlot(int $slotId): ?BookableSlot;

    public function countScheduledSeats(int $slotId, \DateTimeImmutable $date): int;

    public function studentHasOverlap(int $studentId, \DateTimeImmutable $date, string $start, string $end): bool;

    public function countActiveForStudent(int $studentId, \DateTimeImmutable $now): int;

    public function findForUpdate(int $id): ?Tutoring;

    /** @throws \Tutorias\Shared\Domain\Conflict si la reserva viola una restricción única o el bloqueo optimista */
    public function save(Tutoring $tutoring): void;
}
