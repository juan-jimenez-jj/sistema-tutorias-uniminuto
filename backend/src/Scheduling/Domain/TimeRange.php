<?php

declare(strict_types=1);

namespace Tutorias\Scheduling\Domain;

use Tutorias\Shared\Domain\ValidationError;

/** Value Object: rango horario dentro de un día, en minutos desde la medianoche. */
final readonly class TimeRange
{
    private function __construct(public int $startMinute, public int $endMinute)
    {
    }

    public static function fromStrings(string $start, string $end): self
    {
        $s = self::toMinutes($start);
        $e = self::toMinutes($end);
        if ($e <= $s) {
            throw ValidationError::field('horaFin', 'La hora de fin debe ser posterior a la hora de inicio.');
        }

        return new self($s, $e);
    }

    public function overlaps(self $other): bool
    {
        return $this->startMinute < $other->endMinute && $other->startMinute < $this->endMinute;
    }

    public function durationMinutes(): int
    {
        return $this->endMinute - $this->startMinute;
    }

    public function start(): string
    {
        return sprintf('%02d:%02d', intdiv($this->startMinute, 60), $this->startMinute % 60);
    }

    public function end(): string
    {
        return sprintf('%02d:%02d', intdiv($this->endMinute, 60), $this->endMinute % 60);
    }

    private static function toMinutes(string $time): int
    {
        if (!preg_match('/^(\d{2}):(\d{2})/', $time, $m) || (int) $m[1] > 23 || (int) $m[2] > 59) {
            throw ValidationError::field('horaInicio', "Hora inválida: {$time}.");
        }

        return (int) $m[1] * 60 + (int) $m[2];
    }
}
