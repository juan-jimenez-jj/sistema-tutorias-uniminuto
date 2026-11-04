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

final class CancelTutoring
{
    public function __construct(
        private readonly TutoringRepository $tutorings,
        private readonly TransactionManager $tx,
        private readonly EventPublisher $events,
        private readonly Clock $clock,
        private readonly BookingPolicy $policy,
        private readonly AuditLog $audit,
    ) {
    }

    public function execute(Actor $actor, int $tutoringId, ?string $reason): Tutoring
    {
        return $this->tx->transactional(function () use ($actor, $tutoringId, $reason): Tutoring {
            $tutoring = $this->tutorings->findForUpdate($tutoringId) ?? throw NotFound::entity('tutoría', $tutoringId);
            $tutoring->cancel($actor->role, $actor->id, $reason, $this->clock->now(), $this->policy);
            $this->tutorings->save($tutoring);
            $this->events->publish(...$tutoring->pullEvents());
            $this->audit->record($actor->id, 'tutoria.cancelada', 'tutoria', $tutoringId, ['motivo' => $tutoring->cancellationReason()]);

            return $tutoring;
        });
    }
}
