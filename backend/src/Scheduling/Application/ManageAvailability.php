<?php

declare(strict_types=1);

namespace Tutorias\Scheduling\Application;

use Tutorias\Scheduling\Domain\AvailabilitySlot;
use Tutorias\Scheduling\Domain\Modality;
use Tutorias\Scheduling\Domain\SlotRepository;
use Tutorias\Scheduling\Domain\TimeRange;
use Tutorias\Scheduling\Domain\Weekday;
use Tutorias\Shared\Application\Actor;
use Tutorias\Shared\Application\AuditLog;
use Tutorias\Shared\Application\Cache;
use Tutorias\Shared\Application\TransactionManager;
use Tutorias\Shared\Domain\BusinessRuleViolation;
use Tutorias\Shared\Domain\Clock;
use Tutorias\Shared\Domain\Conflict;
use Tutorias\Shared\Domain\Forbidden;
use Tutorias\Shared\Domain\NotFound;
use Tutorias\Shared\Domain\Role;

final class ManageAvailability
{
    public function __construct(
        private readonly SlotRepository $slots,
        private readonly TeachingAssignments $assignments,
        private readonly BookingsOfSlot $bookings,
        private readonly TransactionManager $tx,
        private readonly Clock $clock,
        private readonly Cache $cache,
        private readonly AuditLog $audit,
    ) {
    }

    public function publish(Actor $teacher, int $subjectId, Weekday $weekday, string $start, string $end, string $location, Modality $modality, int $capacity): AvailabilitySlot
    {
        $teacher->mustBe(Role::Docente);
        $this->assertTeaches($teacher->id, $subjectId);
        $slot = AvailabilitySlot::publish($teacher->id, $subjectId, $weekday, TimeRange::fromStrings($start, $end), $location, $modality, $capacity);

        $this->tx->transactional(function () use ($slot): void {
            $this->assertNoOverlap($slot);
            $this->slots->save($slot);
        });
        $this->cache->forgetPrefix('catalogo.');
        $this->audit->record($teacher->id, 'franja.publicada', 'franja', $slot->id(), SlotView::from($slot));

        return $slot;
    }

    public function update(Actor $teacher, int $slotId, int $subjectId, Weekday $weekday, string $start, string $end, string $location, Modality $modality, int $capacity): AvailabilitySlot
    {
        $slot = $this->ownedSlot($teacher, $slotId);
        $this->assertTeaches($teacher->id, $subjectId);
        $time = TimeRange::fromStrings($start, $end);
        $now = $this->clock->now();

        if ($slot->changesSchedule($subjectId, $weekday, $time) && $this->bookings->futureScheduledCount($slotId, $now) > 0) {
            throw BusinessRuleViolation::rule(
                'slot_has_bookings',
                'La franja tiene tutorías programadas. Solo puedes cambiar el lugar o el cupo; para cambiar día, hora o materia crea una franja nueva.'
            );
        }
        $maxBooked = $this->bookings->maxFutureScheduledPerDate($slotId, $now);
        if ($capacity < $maxBooked) {
            throw BusinessRuleViolation::rule('capacity_below_bookings', "No puedes reducir el cupo a {$capacity}: ya hay {$maxBooked} estudiantes inscritos en una fecha.");
        }

        $slot->reschedule($subjectId, $weekday, $time, $location, $modality, $capacity);
        $this->tx->transactional(function () use ($slot): void {
            $this->assertNoOverlap($slot);
            $this->slots->save($slot);
        });
        $this->cache->forgetPrefix('catalogo.');
        $this->audit->record($teacher->id, 'franja.actualizada', 'franja', $slotId, SlotView::from($slot));

        return $slot;
    }

    public function setActive(Actor $teacher, int $slotId, bool $active): AvailabilitySlot
    {
        $slot = $this->ownedSlot($teacher, $slotId);
        if ($active) {
            $this->assertTeaches($teacher->id, $slot->subjectId());
            $slot->activate();
            $this->assertNoOverlap($slot);
        } else {
            $pending = $this->bookings->futureScheduledCount($slotId, $this->clock->now());
            if ($pending > 0) {
                throw BusinessRuleViolation::rule('slot_has_bookings', "La franja tiene {$pending} tutoría(s) programada(s). Cancélalas antes de desactivarla.");
            }
            $slot->deactivate();
        }
        $this->slots->save($slot);
        $this->cache->forgetPrefix('catalogo.');
        $this->audit->record($teacher->id, $active ? 'franja.activada' : 'franja.desactivada', 'franja', $slotId);

        return $slot;
    }

    private function ownedSlot(Actor $teacher, int $slotId): AvailabilitySlot
    {
        $teacher->mustBe(Role::Docente);
        $slot = $this->slots->findById($slotId) ?? throw NotFound::entity('franja', $slotId);
        if ($slot->teacherId() !== $teacher->id) {
            throw Forbidden::because('Solo puedes modificar tus propias franjas.');
        }

        return $slot;
    }

    private function assertTeaches(int $teacherId, int $subjectId): void
    {
        if (!$this->assignments->teaches($teacherId, $subjectId)) {
            throw Forbidden::because('No tienes asignada esa materia. Solicita la asignación a coordinación.');
        }
    }

    private function assertNoOverlap(AvailabilitySlot $slot): void
    {
        foreach ($this->slots->activeSlotsOfTeacherOn($slot->teacherId(), $slot->weekday()) as $other) {
            if ($slot->conflictsWith($other)) {
                throw Conflict::because('slot_overlap', sprintf(
                    'Se cruza con tu franja del %s de %s a %s.',
                    $other->weekday()->label(),
                    $other->time()->start(),
                    $other->time()->end(),
                ));
            }
        }
    }
}
