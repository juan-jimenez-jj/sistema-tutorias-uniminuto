<?php

declare(strict_types=1);

namespace Tutorias\Booking\Application;

use Tutorias\Booking\Domain\Tutoring;
use Tutorias\Booking\Domain\TutoringRepository;
use Tutorias\Shared\Application\Actor;
use Tutorias\Shared\Application\EventPublisher;
use Tutorias\Shared\Application\TransactionManager;
use Tutorias\Shared\Domain\Clock;
use Tutorias\Shared\Domain\NotFound;
use Tutorias\Shared\Domain\Role;

final class RateTutoring
{
    public function __construct(
        private readonly TutoringRepository $tutorings,
        private readonly TransactionManager $tx,
        private readonly EventPublisher $events,
        private readonly Clock $clock,
    ) {
    }

    public function execute(Actor $student, int $tutoringId, int $score, ?string $comment): Tutoring
    {
        $student->mustBe(Role::Estudiante);

        return $this->tx->transactional(function () use ($student, $tutoringId, $score, $comment): Tutoring {
            $tutoring = $this->tutorings->findForUpdate($tutoringId) ?? throw NotFound::entity('tutoría', $tutoringId);
            $tutoring->rate($student->id, $score, $comment, $this->clock->now());
            $this->tutorings->save($tutoring);
            $this->events->publish(...$tutoring->pullEvents());

            return $tutoring;
        });
    }
}
