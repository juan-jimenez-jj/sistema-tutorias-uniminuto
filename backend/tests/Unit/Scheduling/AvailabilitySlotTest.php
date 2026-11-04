<?php

declare(strict_types=1);

namespace Tutorias\Tests\Unit\Scheduling;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tutorias\Scheduling\Domain\AvailabilitySlot;
use Tutorias\Scheduling\Domain\Modality;
use Tutorias\Scheduling\Domain\TimeRange;
use Tutorias\Scheduling\Domain\Weekday;
use Tutorias\Shared\Domain\ValidationError;

final class AvailabilitySlotTest extends TestCase
{
    #[Test]
    public function detecta_cruces_de_horario_el_mismo_dia(): void
    {
        $a = $this->slot(Weekday::Lunes, '10:00', '11:00', 1);
        self::assertTrue($a->conflictsWith($this->slot(Weekday::Lunes, '10:30', '11:30', 2)));
        self::assertFalse($a->conflictsWith($this->slot(Weekday::Lunes, '11:00', '12:00', 3)), 'Franjas contiguas no se cruzan');
        self::assertFalse($a->conflictsWith($this->slot(Weekday::Martes, '10:00', '11:00', 4)));
    }

    #[Test]
    public function una_franja_inactiva_no_genera_cruce(): void
    {
        $other = $this->slot(Weekday::Lunes, '10:00', '11:00', 2);
        $other->deactivate();
        self::assertFalse($this->slot(Weekday::Lunes, '10:00', '11:00', 1)->conflictsWith($other));
    }

    #[Test]
    public function rechaza_duraciones_y_horarios_fuera_de_limites(): void
    {
        foreach ([['10:00', '10:15'], ['08:00', '13:00'], ['05:00', '06:00'], ['21:30', '22:30']] as [$start, $end]) {
            try {
                $this->slot(Weekday::Lunes, $start, $end);
                self::fail("Debió rechazar {$start}-{$end}");
            } catch (ValidationError) {
                $this->addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function hora_fin_debe_ser_posterior_a_inicio(): void
    {
        $this->expectException(ValidationError::class);
        TimeRange::fromStrings('11:00', '10:00');
    }

    #[Test]
    public function cupo_entre_1_y_30(): void
    {
        $this->expectException(ValidationError::class);
        AvailabilitySlot::publish(1, 1, Weekday::Lunes, TimeRange::fromStrings('10:00', '11:00'), 'B402', Modality::Presencial, 31);
    }

    private function slot(Weekday $day, string $start, string $end, ?int $id = null): AvailabilitySlot
    {
        $slot = AvailabilitySlot::publish(1, 1, $day, TimeRange::fromStrings($start, $end), 'B402', Modality::Presencial, 2);
        if ($id !== null) {
            $slot->assignId($id);
        }

        return $slot;
    }
}
