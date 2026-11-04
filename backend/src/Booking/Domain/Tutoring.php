<?php

declare(strict_types=1);

namespace Tutorias\Booking\Domain;

use Tutorias\Booking\Domain\Event\TutoringBooked;
use Tutorias\Booking\Domain\Event\TutoringCancelled;
use Tutorias\Booking\Domain\Event\TutoringCompleted;
use Tutorias\Booking\Domain\Event\TutoringMarkedNoShow;
use Tutorias\Booking\Domain\Event\TutoringRated;
use Tutorias\Shared\Domain\BusinessRuleViolation;
use Tutorias\Shared\Domain\Conflict;
use Tutorias\Shared\Domain\Forbidden;
use Tutorias\Shared\Domain\RecordsEvents;
use Tutorias\Shared\Domain\Role;
use Tutorias\Shared\Domain\ValidationError;

/**
 * Agregado Tutoría. Concentra TODAS las reglas de negocio del ciclo de vida
 * (reserva → completada | cancelada | no asistió → calificada), que en el sistema
 * heredado estaban repartidas entre páginas PHP con HTML y SQL mezclados.
 */
final class Tutoring
{
    use RecordsEvents;

    private function __construct(
        private ?int $id,
        private readonly string $code,
        private readonly int $studentId,
        private readonly int $teacherId,
        private readonly int $subjectId,
        private readonly int $slotId,
        private readonly \DateTimeImmutable $date,
        private readonly string $start,
        private readonly string $end,
        private readonly string $location,
        private readonly string $modality,
        private readonly string $topic,
        private TutoringStatus $status,
        private ?Role $cancelledBy = null,
        private ?string $cancellationReason = null,
        private ?\DateTimeImmutable $cancelledAt = null,
        private ?string $teacherNotes = null,
        private ?\DateTimeImmutable $completedAt = null,
        private ?int $rating = null,
        private ?string $studentComment = null,
        private ?\DateTimeImmutable $ratedAt = null,
        private int $version = 1,
    ) {
    }

    /**
     * Reserva una tutoría. Las precondiciones que dependen del estado global
     * (cupo ocupado, cruces, reservas activas) las calcula el caso de uso bajo bloqueo
     * y se validan aquí, de modo que la regla vive en un solo lugar.
     */
    public static function book(
        string $code,
        int $studentId,
        BookableSlot $slot,
        \DateTimeImmutable $date,
        string $topic,
        \DateTimeImmutable $now,
        BookingPolicy $policy,
        int $seatsTaken,
        bool $studentHasOverlap,
        int $studentActiveBookings,
    ): self {
        $topic = trim($topic);
        if (mb_strlen($topic) < 5) {
            throw ValidationError::field('tema', 'Describe el tema a tratar (mínimo 5 caracteres).');
        }
        if (!$slot->isBookable()) {
            throw BusinessRuleViolation::rule('slot_not_available', 'La franja seleccionada ya no está disponible.');
        }
        if ((int) $date->format('N') !== $slot->weekday) {
            throw BusinessRuleViolation::rule('date_weekday_mismatch', 'La fecha elegida no corresponde al día de la franja.');
        }

        $startsAt = self::at($date, $slot->start, $now);
        if ($startsAt < $now->modify("+{$policy->minAdvanceMinutes} minutes")) {
            throw BusinessRuleViolation::rule('too_late_to_book', "Las tutorías se reservan con al menos {$policy->minAdvanceMinutes} minutos de anticipación.");
        }
        if ($date > $now->setTime(0, 0)->modify("+{$policy->maxDaysAhead} days")) {
            throw BusinessRuleViolation::rule('too_far_ahead', "Solo puedes reservar dentro de los próximos {$policy->maxDaysAhead} días.");
        }
        if ($seatsTaken >= $slot->capacity) {
            throw Conflict::because('slot_full', 'No quedan cupos en esa franja para la fecha elegida.');
        }
        if ($studentHasOverlap) {
            throw Conflict::because('student_overlap', 'Ya tienes otra tutoría programada que se cruza con ese horario.');
        }
        if ($studentActiveBookings >= $policy->maxActivePerStudent) {
            throw BusinessRuleViolation::rule('too_many_active', "Puedes tener máximo {$policy->maxActivePerStudent} tutorías programadas a la vez.");
        }

        $tutoring = new self(
            null,
            $code,
            $studentId,
            $slot->teacherId,
            $slot->subjectId,
            $slot->id,
            $date,
            $slot->start,
            $slot->end,
            $slot->location,
            $slot->modality,
            $topic,
            TutoringStatus::Programada,
        );
        $tutoring->pendingBookedEvent = [$slot, $now];

        return $tutoring;
    }

    /** @var array{BookableSlot, \DateTimeImmutable}|null el evento necesita el id, que se asigna al persistir */
    private ?array $pendingBookedEvent = null;

    public function assignId(int $id): void
    {
        if ($this->id !== null) {
            throw new \LogicException('La tutoría ya tiene identificador.');
        }
        $this->id = $id;
        if ($this->pendingBookedEvent !== null) {
            [$slot, $now] = $this->pendingBookedEvent;
            $this->record(new TutoringBooked($id, $this->snapshot() + [
                'docenteNombre' => $slot->teacherName,
                'materiaNombre' => $slot->subjectName,
            ], $now));
            $this->pendingBookedEvent = null;
        }
    }

    public function cancel(Role $byRole, int $byUserId, ?string $reason, \DateTimeImmutable $now, BookingPolicy $policy): void
    {
        $this->assertStatus(TutoringStatus::Programada, 'Solo se pueden cancelar tutorías programadas.');
        $startsAt = $this->startsAt($now);
        if ($startsAt <= $now) {
            throw BusinessRuleViolation::rule('already_started', 'La tutoría ya inició o pasó; no se puede cancelar.');
        }

        switch ($byRole) {
            case Role::Estudiante:
                if ($byUserId !== $this->studentId) {
                    throw Forbidden::because('Solo puedes cancelar tus propias tutorías.');
                }
                // Regla heredada del sistema original: mínimo 3 horas de anticipación.
                if ($startsAt < $now->modify("+{$policy->cancellationWindowHours} hours")) {
                    throw BusinessRuleViolation::rule(
                        'cancellation_window_closed',
                        "Solo puedes cancelar con al menos {$policy->cancellationWindowHours} horas de anticipación. Comunícate con el docente."
                    );
                }
                $reason = $reason ?: 'Cancelada por el estudiante';
                break;
            case Role::Docente:
                if ($byUserId !== $this->teacherId) {
                    throw Forbidden::because('Solo puedes cancelar tutorías de tu agenda.');
                }
                if ($reason === null || mb_strlen(trim($reason)) < 5) {
                    throw ValidationError::field('motivo', 'Indica al estudiante el motivo de la cancelación (mínimo 5 caracteres).');
                }
                break;
            case Role::Administrador:
                $reason = $reason ?: 'Cancelada por coordinación académica';
                break;
        }

        $this->status = TutoringStatus::Cancelada;
        $this->cancelledBy = $byRole;
        $this->cancellationReason = trim((string) $reason);
        $this->cancelledAt = $now;
        $this->record(new TutoringCancelled($this->id(), $this->snapshot() + [
            'canceladaPor' => $byRole->value,
            'motivo' => $this->cancellationReason,
        ], $now));
    }

    public function complete(int $teacherId, ?string $notes, \DateTimeImmutable $now): void
    {
        $this->assertTeacher($teacherId);
        $this->assertStatus(TutoringStatus::Programada, 'Solo se pueden completar tutorías programadas.');
        if ($this->startsAt($now) > $now) {
            throw BusinessRuleViolation::rule('not_started_yet', 'No puedes marcar como completada una tutoría que aún no ha iniciado.');
        }
        $this->status = TutoringStatus::Completada;
        $this->teacherNotes = $notes !== null ? trim($notes) : null;
        $this->completedAt = $now;
        $this->record(new TutoringCompleted($this->id(), $this->snapshot(), $now));
    }

    public function markNoShow(int $teacherId, ?string $notes, \DateTimeImmutable $now): void
    {
        $this->assertTeacher($teacherId);
        $this->assertStatus(TutoringStatus::Programada, 'Solo se puede registrar inasistencia en tutorías programadas.');
        if ($this->startsAt($now) > $now) {
            throw BusinessRuleViolation::rule('not_started_yet', 'La inasistencia se registra a partir de la hora de inicio.');
        }
        $this->status = TutoringStatus::NoAsistio;
        $this->teacherNotes = $notes !== null ? trim($notes) : null;
        $this->completedAt = $now;
        $this->record(new TutoringMarkedNoShow($this->id(), $this->snapshot(), $now));
    }

    public function rate(int $studentId, int $score, ?string $comment, \DateTimeImmutable $now): void
    {
        if ($studentId !== $this->studentId) {
            throw Forbidden::because('Solo el estudiante de la tutoría puede calificarla.');
        }
        $this->assertStatus(TutoringStatus::Completada, 'Solo puedes calificar tutorías completadas.');
        if ($this->rating !== null) {
            throw Conflict::because('already_rated', 'Ya calificaste esta tutoría.');
        }
        if ($score < 1 || $score > 5) {
            throw ValidationError::field('calificacion', 'La calificación debe estar entre 1 y 5.');
        }
        $this->rating = $score;
        $this->studentComment = $comment !== null ? trim($comment) : null;
        $this->ratedAt = $now;
        $this->record(new TutoringRated($this->id(), $this->snapshot() + ['calificacion' => $score], $now));
    }

    public function startsAt(\DateTimeImmutable $reference): \DateTimeImmutable
    {
        return self::at($this->date, $this->start, $reference);
    }

    private static function at(\DateTimeImmutable $date, string $time, \DateTimeImmutable $reference): \DateTimeImmutable
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $date->setTimezone($reference->getTimezone())->setTime($h, $m);
    }

    private function assertStatus(TutoringStatus $expected, string $message): void
    {
        if ($this->status !== $expected) {
            throw BusinessRuleViolation::rule('invalid_status', $message . " Estado actual: {$this->status->label()}.");
        }
    }

    private function assertTeacher(int $teacherId): void
    {
        if ($teacherId !== $this->teacherId) {
            throw Forbidden::because('Esta tutoría no pertenece a tu agenda.');
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(): array
    {
        return [
            'codigo' => $this->code,
            'estudianteId' => $this->studentId,
            'docenteId' => $this->teacherId,
            'materiaId' => $this->subjectId,
            'fecha' => $this->date->format('Y-m-d'),
            'horaInicio' => $this->start,
            'horaFin' => $this->end,
            'lugar' => $this->location,
            'modalidad' => $this->modality,
            'tema' => $this->topic,
        ];
    }

    /** @param array<string, mixed> $row */
    public static function fromPersistence(array $row): self
    {
        $tz = new \DateTimeZone('America/Bogota');
        $dt = static fn ($v) => $v ? new \DateTimeImmutable((string) $v, $tz) : null;

        return new self(
            (int) $row['id'],
            (string) $row['codigo'],
            (int) $row['estudiante_id'],
            (int) $row['docente_id'],
            (int) $row['materia_id'],
            (int) $row['franja_id'],
            new \DateTimeImmutable((string) $row['fecha'], $tz),
            substr((string) $row['hora_inicio'], 0, 5),
            substr((string) $row['hora_fin'], 0, 5),
            (string) $row['lugar'],
            (string) $row['modalidad'],
            (string) $row['tema'],
            TutoringStatus::from((string) $row['estado']),
            $row['cancelada_por'] ? Role::from((string) $row['cancelada_por']) : null,
            $row['motivo_cancelacion'],
            $dt($row['cancelada_en']),
            $row['observaciones_docente'],
            $dt($row['completada_en']),
            $row['calificacion'] !== null ? (int) $row['calificacion'] : null,
            $row['comentario_estudiante'],
            $dt($row['calificada_en']),
            (int) $row['version'],
        );
    }

    public function id(): int
    {
        return $this->id ?? throw new \LogicException('Tutoría no persistida.');
    }

    public function hasId(): bool
    { return $this->id !== null; }
    public function code(): string
    { return $this->code; }
    public function studentId(): int
    { return $this->studentId; }
    public function teacherId(): int
    { return $this->teacherId; }
    public function subjectId(): int
    { return $this->subjectId; }
    public function slotId(): int
    { return $this->slotId; }
    public function date(): \DateTimeImmutable
    { return $this->date; }
    public function start(): string
    { return $this->start; }
    public function end(): string
    { return $this->end; }
    public function location(): string
    { return $this->location; }
    public function modality(): string
    { return $this->modality; }
    public function topic(): string
    { return $this->topic; }
    public function status(): TutoringStatus
    { return $this->status; }
    public function cancelledBy(): ?Role
    { return $this->cancelledBy; }
    public function cancellationReason(): ?string
    { return $this->cancellationReason; }
    public function cancelledAt(): ?\DateTimeImmutable
    { return $this->cancelledAt; }
    public function teacherNotes(): ?string
    { return $this->teacherNotes; }
    public function completedAt(): ?\DateTimeImmutable
    { return $this->completedAt; }
    public function rating(): ?int
    { return $this->rating; }
    public function studentComment(): ?string
    { return $this->studentComment; }
    public function ratedAt(): ?\DateTimeImmutable
    { return $this->ratedAt; }
    public function version(): int
    { return $this->version; }

    public function incrementVersion(): void
    {
        $this->version++;
    }
}
