<?php

declare(strict_types=1);

namespace Tutorias\Booking\Domain\Event;

final class TutoringCancelled extends TutoringEvent
{
    public function eventName(): string
    {
        return 'tutoria.cancelada';
    }
}
