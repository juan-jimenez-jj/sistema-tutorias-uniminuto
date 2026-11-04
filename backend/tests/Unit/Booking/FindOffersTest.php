<?php

declare(strict_types=1);

namespace Tutorias\Tests\Unit\Booking;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tutorias\Booking\Application\FindOffers;
use Tutorias\Booking\Application\OfferQueries;
use Tutorias\Booking\Domain\BookingPolicy;
use Tutorias\Shared\Application\Actor;
use Tutorias\Shared\Domain\Role;
use Tutorias\Tests\Support\FrozenClock;

final class FindOffersTest extends TestCase
{
    #[Test]
    public function calcula_cupos_libres_por_fecha_y_omite_franjas_demasiado_proximas(): void
    {
        $queries = new class () implements OfferQueries {
            public function bookableSlots(int $subjectId, ?int $teacherId): array
            {
                return [
                    ['id' => 1, 'docenteId' => 2, 'docenteNombre' => 'Ana', 'diaSemana' => 1, 'horaInicio' => '08:30', 'horaFin' => '09:30', 'lugar' => 'B402', 'modalidad' => 'presencial', 'cupo' => 3],
                    ['id' => 2, 'docenteId' => 2, 'docenteNombre' => 'Ana', 'diaSemana' => 3, 'horaInicio' => '14:00', 'horaFin' => '15:00', 'lugar' => 'B402', 'modalidad' => 'presencial', 'cupo' => 1],
                ];
            }

            public function takenSeats(array $slotIds, \DateTimeImmutable $from, \DateTimeImmutable $to): array
            {
                return ['1|2027-03-08' => 2, '2|2027-03-03' => 1];
            }

            public function studentBookings(int $studentId, \DateTimeImmutable $from, \DateTimeImmutable $to): array
            {
                return ['1|2027-03-08' => true];
            }
        };

        $result = (new FindOffers($queries, new FrozenClock('2027-03-01 08:00'), new BookingPolicy(60, 14, 3, 5)))
            ->execute(new Actor(10, Role::Estudiante, 'Laura', 'laura@uniminuto.edu.co'), 1, null);

        $byDate = array_column($result['dias'], null, 'fecha');
        // Hoy lunes 08:00: la franja de las 08:30 no cumple la anticipación mínima de 60 minutos.
        self::assertArrayNotHasKey('2027-03-01', $byDate);
        self::assertSame(0, $byDate['2027-03-03']['franjas'][0]['cuposDisponibles']);
        self::assertSame(1, $byDate['2027-03-08']['franjas'][0]['cuposDisponibles']);
        self::assertTrue($byDate['2027-03-08']['franjas'][0]['yaReservada']);
        self::assertSame(3, $byDate['2027-03-15']['franjas'][0]['cuposDisponibles']);
        self::assertSame('2027-03-15', $result['hasta']);
    }
}
