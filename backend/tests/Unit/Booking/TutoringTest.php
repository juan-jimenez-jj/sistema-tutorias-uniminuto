<?php

declare(strict_types=1);

namespace Tutorias\Tests\Unit\Booking;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tutorias\Booking\Domain\BookableSlot;
use Tutorias\Booking\Domain\BookingPolicy;
use Tutorias\Booking\Domain\Event\TutoringBooked;
use Tutorias\Booking\Domain\Event\TutoringCancelled;
use Tutorias\Booking\Domain\Event\TutoringCompleted;
use Tutorias\Booking\Domain\Tutoring;
use Tutorias\Booking\Domain\TutoringStatus;
use Tutorias\Shared\Domain\BusinessRuleViolation;
use Tutorias\Shared\Domain\Conflict;
use Tutorias\Shared\Domain\DomainError;
use Tutorias\Shared\Domain\Forbidden;
use Tutorias\Shared\Domain\Role;
use Tutorias\Shared\Domain\ValidationError;

/** Reglas de negocio del agregado Tutoría, sin base de datos ni HTTP. */
final class TutoringTest extends TestCase
{
    private const STUDENT = 10;
    private const TEACHER = 20;

    private BookingPolicy $policy;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->policy = new BookingPolicy(minAdvanceMinutes: 60, maxDaysAhead: 28, cancellationWindowHours: 3, maxActivePerStudent: 5);
        $this->now = $this->at('2027-03-01 08:00'); // lunes
    }

    #[Test]
    public function reserva_valida_queda_programada_y_emite_evento(): void
    {
        $tutoring = $this->book('2027-03-08');
        $tutoring->assignId(99);

        self::assertSame(TutoringStatus::Programada, $tutoring->status());
        self::assertSame('10:00', $tutoring->start());
        $events = $tutoring->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(TutoringBooked::class, $events[0]);
        self::assertSame('tutoria.reservada', $events[0]->eventName());
        self::assertSame(99, $events[0]->payload()['tutoriaId']);
        self::assertSame('Ana Docente', $events[0]->payload()['docenteNombre']);
    }

    /** @return iterable<string, array{array<string, mixed>, class-string<DomainError>, string}> */
    public static function reservasInvalidas(): iterable
    {
        yield 'fecha que no es lunes' => [['date' => '2027-03-09'], BusinessRuleViolation::class, 'date_weekday_mismatch'];
        yield 'menos de 60 minutos de anticipación' => [['date' => '2027-03-01', 'now' => '2027-03-01 09:30'], BusinessRuleViolation::class, 'too_late_to_book'];
        yield 'en el pasado' => [['date' => '2027-02-22'], BusinessRuleViolation::class, 'too_late_to_book'];
        yield 'más allá de 28 días' => [['date' => '2027-04-05'], BusinessRuleViolation::class, 'too_far_ahead'];
        yield 'sin cupo' => [['seats' => 2], Conflict::class, 'slot_full'];
        yield 'cruce con otra tutoría' => [['overlap' => true], Conflict::class, 'student_overlap'];
        yield 'demasiadas reservas activas' => [['active' => 5], BusinessRuleViolation::class, 'too_many_active'];
        yield 'franja inactiva' => [['slotActive' => false], BusinessRuleViolation::class, 'slot_not_available'];
        yield 'docente ya no dicta la materia' => [['teaches' => false], BusinessRuleViolation::class, 'slot_not_available'];
        yield 'tema vacío' => [['topic' => '  '], ValidationError::class, 'validation_failed'];
    }

    /** @param array<string, mixed> $o @param class-string<DomainError> $exception */
    #[Test]
    #[DataProvider('reservasInvalidas')]
    public function rechaza_reservas_que_violan_reglas(array $o, string $exception, string $code): void
    {
        try {
            Tutoring::book(
                'TUT-TEST01',
                self::STUDENT,
                $this->slot(active: $o['slotActive'] ?? true, teaches: $o['teaches'] ?? true),
                $this->date($o['date'] ?? '2027-03-08'),
                $o['topic'] ?? 'Derivadas',
                isset($o['now']) ? $this->at($o['now']) : $this->now,
                $this->policy,
                seatsTaken: $o['seats'] ?? 0,
                studentHasOverlap: $o['overlap'] ?? false,
                studentActiveBookings: $o['active'] ?? 0,
            );
            self::fail('Se esperaba ' . $exception);
        } catch (DomainError $e) {
            self::assertInstanceOf($exception, $e);
            self::assertSame($code, $e->errorCode());
        }
    }

    #[Test]
    public function estudiante_no_puede_cancelar_con_menos_de_tres_horas(): void
    {
        $t = $this->persisted('2027-03-01'); // inicia 10:00; ahora 08:00 → faltan 2 h
        try {
            $t->cancel(Role::Estudiante, self::STUDENT, null, $this->now, $this->policy);
            self::fail('Debió rechazar la cancelación tardía');
        } catch (BusinessRuleViolation $e) {
            self::assertSame('cancellation_window_closed', $e->errorCode());
            self::assertSame(TutoringStatus::Programada, $t->status());
        }
    }

    #[Test]
    public function estudiante_puede_cancelar_justo_en_el_limite_de_tres_horas(): void
    {
        $t = $this->persisted('2027-03-01');
        $t->cancel(Role::Estudiante, self::STUDENT, null, $this->at('2027-03-01 07:00'), $this->policy);

        self::assertSame(TutoringStatus::Cancelada, $t->status());
        self::assertSame(Role::Estudiante, $t->cancelledBy());
        self::assertInstanceOf(TutoringCancelled::class, $t->pullEvents()[0]);
    }

    #[Test]
    public function un_estudiante_no_puede_cancelar_la_tutoria_de_otro(): void
    {
        $this->expectException(Forbidden::class);
        $this->persisted('2027-03-08')->cancel(Role::Estudiante, 777, null, $this->now, $this->policy);
    }

    #[Test]
    public function docente_debe_indicar_motivo_y_puede_cancelar_a_ultima_hora(): void
    {
        $t = $this->persisted('2027-03-01');
        try {
            $t->cancel(Role::Docente, self::TEACHER, '', $this->at('2027-03-01 09:50'), $this->policy);
            self::fail('Debe exigir motivo');
        } catch (ValidationError) {
        }
        $t->cancel(Role::Docente, self::TEACHER, 'Reunión de facultad', $this->at('2027-03-01 09:50'), $this->policy);
        self::assertSame('Reunión de facultad', $t->cancellationReason());
    }

    #[Test]
    public function no_se_puede_cancelar_una_tutoria_que_ya_inicio(): void
    {
        $this->expectException(BusinessRuleViolation::class);
        $this->persisted('2027-03-01')->cancel(Role::Administrador, 1, null, $this->at('2027-03-01 10:05'), $this->policy);
    }

    #[Test]
    public function docente_completa_solo_despues_de_iniciar(): void
    {
        $t = $this->persisted('2027-03-01');
        try {
            $t->complete(self::TEACHER, null, $this->at('2027-03-01 09:59'));
            self::fail('No debe completar antes de iniciar');
        } catch (BusinessRuleViolation $e) {
            self::assertSame('not_started_yet', $e->errorCode());
        }
        $t->complete(self::TEACHER, 'Se resolvió el taller', $this->at('2027-03-01 11:00'));
        self::assertSame(TutoringStatus::Completada, $t->status());
        self::assertInstanceOf(TutoringCompleted::class, $t->pullEvents()[0]);
    }

    #[Test]
    public function otro_docente_no_puede_cerrar_la_tutoria(): void
    {
        $this->expectException(Forbidden::class);
        $this->persisted('2027-03-01')->complete(999, null, $this->at('2027-03-01 11:00'));
    }

    #[Test]
    public function calificacion_solo_una_vez_y_solo_si_esta_completada(): void
    {
        $t = $this->persisted('2027-03-01');
        try {
            $t->rate(self::STUDENT, 5, null, $this->now);
            self::fail('No se califica una tutoría programada');
        } catch (BusinessRuleViolation) {
        }
        $t->complete(self::TEACHER, null, $this->at('2027-03-01 11:00'));
        $t->rate(self::STUDENT, 4, 'Muy clara', $this->at('2027-03-01 12:00'));
        self::assertSame(4, $t->rating());

        $this->expectException(Conflict::class);
        $t->rate(self::STUDENT, 5, null, $this->at('2027-03-01 12:05'));
    }

    #[Test]
    public function calificacion_fuera_de_rango_es_invalida(): void
    {
        $t = $this->persisted('2027-03-01');
        $t->complete(self::TEACHER, null, $this->at('2027-03-01 11:00'));
        $this->expectException(ValidationError::class);
        $t->rate(self::STUDENT, 6, null, $this->at('2027-03-01 12:00'));
    }

    #[Test]
    public function inasistencia_cierra_la_tutoria(): void
    {
        $t = $this->persisted('2027-03-01');
        $t->markNoShow(self::TEACHER, null, $this->at('2027-03-01 10:20'));
        self::assertSame(TutoringStatus::NoAsistio, $t->status());
    }

    private function book(string $date): Tutoring
    {
        return Tutoring::book('TUT-TEST01', self::STUDENT, $this->slot(), $this->date($date), 'Derivadas', $this->now, $this->policy, 0, false, 0);
    }

    private function persisted(string $date): Tutoring
    {
        $t = Tutoring::book('TUT-TEST01', self::STUDENT, $this->slot(), $this->date($date), 'Derivadas', $this->at('2027-02-26 08:00'), $this->policy, 0, false, 0);
        $t->assignId(1);
        $t->pullEvents();

        return $t;
    }

    private function slot(bool $active = true, bool $teaches = true): BookableSlot
    {
        return new BookableSlot(5, self::TEACHER, 'Ana Docente', 1, 'Cálculo Diferencial', 1, '10:00', '11:00', 'B402', 'presencial', 2, $active, true, true, $teaches);
    }

    private function date(string $ymd): \DateTimeImmutable
    {
        return new \DateTimeImmutable($ymd . ' 00:00', new \DateTimeZone('America/Bogota'));
    }

    private function at(string $moment): \DateTimeImmutable
    {
        return new \DateTimeImmutable($moment, new \DateTimeZone('America/Bogota'));
    }
}
