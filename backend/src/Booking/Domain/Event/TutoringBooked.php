<?php

declare(strict_types=1);

namespace Tutorias\Booking\Domain\Event;

final class TutoringBooked extends TutoringEvent
{
    public function eventName(): string
    {
        return 'tutoria.reservada';
    }
}
