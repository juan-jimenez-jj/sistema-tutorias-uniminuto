<?php

declare(strict_types=1);

namespace Tutorias\Booking\Domain;

enum TutoringStatus: string
{
    case Programada = 'programada';
    case Completada = 'completada';
    case Cancelada = 'cancelada';
    case NoAsistio = 'no_asistio';

    public function label(): string
    {
        return match ($this) {
            self::Programada => 'Programada',
            self::Completada => 'Completada',
            self::Cancelada => 'Cancelada',
            self::NoAsistio => 'No asistió',
        };
    }
}
