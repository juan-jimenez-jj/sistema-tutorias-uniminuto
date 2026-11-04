<?php

declare(strict_types=1);

namespace Tutorias\Scheduling\Domain;

/** Días hábiles de tutoría (ISO-8601: 1 = lunes). La sede no programa tutorías los domingos. */
enum Weekday: int
{
    case Lunes = 1;
    case Martes = 2;
    case Miercoles = 3;
    case Jueves = 4;
    case Viernes = 5;
    case Sabado = 6;

    public function label(): string
    {
        return match ($this) {
            self::Lunes => 'Lunes',
            self::Martes => 'Martes',
            self::Miercoles => 'Miércoles',
            self::Jueves => 'Jueves',
            self::Viernes => 'Viernes',
            self::Sabado => 'Sábado',
        };
    }

    public static function ofDate(\DateTimeInterface $date): ?self
    {
        return self::tryFrom((int) $date->format('N'));
    }
}
