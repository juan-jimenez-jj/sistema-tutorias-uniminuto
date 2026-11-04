<?php

declare(strict_types=1);

namespace Tutorias\Booking\Application;

use Tutorias\Booking\Domain\BookingPolicy;
use Tutorias\Booking\Domain\Tutoring;
use Tutorias\Booking\Domain\TutoringRepository;
use Tutorias\Shared\Application\Actor;
use Tutorias\Shared\Application\AuditLog;
use Tutorias\Shared\Application\EventPublisher;
use Tutorias\Shared\Application\TransactionManager;
use Tutorias\Shared\Domain\Clock;
use Tutorias\Shared\Domain\NotFound;
use Tutorias\Shared\Domain\Role;

/**
 * Caso de uso crítico: reservar una tutoría (ruta crítica T28 del plan).
 *
 * Táctica de control de concurrencia en tres capas:
 *  1. Bloqueo pesimista de la franja (SELECT … FOR UPDATE): dos estudiantes que compiten
 *     por el último cupo se atienden en serie, nunca a la vez.
 *  2. Validación de cupo/cruces DENTRO de la misma transacción que inserta.
 *  3. Índice único en BD (franja, fecha, estudiante, reserva_activa) como red de seguridad.
 * El evento "tutoria.reservada" se escribe en la outbox en la misma transacción.
 */
final class BookTutoring
{
    public function __construct(
        private readonly TutoringRepository $tutorings,
        private readonly TransactionManager $tx,
        private readonly EventPublisher $events,
        private readonly Clock $clock,
        private readonly BookingPolicy $policy,
        private readonly CodeGenerator $codes,
        private readonly AuditLog $audit,
    ) {
    }

    public function execute(Actor $student, int $slotId, \DateTimeImmutable $date, string $topic): Tutoring
    {
        $student->mustBe(Role::Estudiante);
        $now = $this->clock->now();

        $tutoring = $this->tx->transactional(function () use ($student, $slotId, $date, $topic, $now): Tutoring {
            $slot = $this->tutorings->lockBookableSlot($slotId) ?? throw NotFound::entity('franja', $slotId);

            $tutoring = Tutoring::book(
                $this->codes->next(),
                $student->id,
                $slot,
                $date,
                $topic,
                $now,
                $this->policy,
                seatsTaken: $this->tutorings->countScheduledSeats($slotId, $date),
                studentHasOverlap: $this->tutorings->studentHasOverlap($student->id, $date, $slot->start, $slot->end),
                studentActiveBookings: $this->tutorings->countActiveForStudent($student->id, $now),
            );
            $this->tutorings->save($tutoring);
            $this->events->publish(...$tutoring->pullEvents());
            $this->audit->record($student->id, 'tutoria.reservada', 'tutoria', $tutoring->id(), ['codigo' => $tutoring->code()]);

            return $tutoring;
        });

        return $tutoring;
    }
}
