<?php

declare(strict_types=1);

namespace Tutorias\Booking\Domain\Event;

final class TutoringCompleted extends TutoringEvent
{
    public function eventName(): string
    {
        return 'tutoria.completada';
    }
}
