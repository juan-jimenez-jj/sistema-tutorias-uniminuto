<?php

declare(strict_types=1);

namespace Tutorias\Booking\Application;

use Tutorias\Booking\Domain\Tutoring;
use Tutorias\Booking\Domain\TutoringRepository;
use Tutorias\Shared\Application\Actor;
use Tutorias\Shared\Application\AuditLog;
use Tutorias\Shared\Application\EventPublisher;
use Tutorias\Shared\Application\TransactionManager;
use Tutorias\Shared\Domain\Clock;
use Tutorias\Shared\Domain\NotFound;
use Tutorias\Shared\Domain\Role;

/** El docente cierra la tutoría: completada (asistió) o inasistencia. */
final class CloseTutoring
{
    public function __construct(
        private readonly TutoringRepository $tutorings,
        private readonly TransactionManager $tx,
        private readonly EventPublisher $events,
        private readonly Clock $clock,
        private readonly AuditLog $audit,
    ) {
    }

    public function execute(Actor $teacher, int $tutoringId, bool $attended, ?string $notes): Tutoring
    {
        $teacher->mustBe(Role::Docente);

        return $this->tx->transactional(function () use ($teacher, $tutoringId, $attended, $notes): Tutoring {
            $tutoring = $this->tutorings->findForUpdate($tutoringId) ?? throw NotFound::entity('tutoría', $tutoringId);
            $attended
                ? $tutoring->complete($teacher->id, $notes, $this->clock->now())
                : $tutoring->markNoShow($teacher->id, $notes, $this->clock->now());
            $this->tutorings->save($tutoring);
            $this->events->publish(...$tutoring->pullEvents());
            $this->audit->record($teacher->id, $attended ? 'tutoria.completada' : 'tutoria.inasistencia', 'tutoria', $tutoringId);

            return $tutoring;
        });
    }
}
