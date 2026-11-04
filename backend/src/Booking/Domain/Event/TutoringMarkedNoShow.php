<?php

declare(strict_types=1);

namespace Tutorias\Booking\Domain\Event;

final class TutoringMarkedNoShow extends TutoringEvent
{
    public function eventName(): string
    {
        return 'tutoria.inasistencia';
    }
}
