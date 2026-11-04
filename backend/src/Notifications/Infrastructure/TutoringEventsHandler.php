<?php

declare(strict_types=1);

namespace Tutorias\Notifications\Infrastructure;

use Tutorias\Notifications\Application\TutoringNotifier;
use Tutorias\Shared\Infrastructure\Outbox\OutboxHandler;
use Tutorias\Shared\Infrastructure\Outbox\OutboxMessage;

/** Adaptador de entrada: conecta los eventos de la outbox con el notificador. */
final class TutoringEventsHandler implements OutboxHandler
{
    public function __construct(private readonly TutoringNotifier $notifier)
    {
    }

    public function subscribedTo(): array
    {
        return ['tutoria.reservada', 'tutoria.cancelada', 'tutoria.completada', 'tutoria.inasistencia'];
    }

    public function handle(OutboxMessage $message): void
    {
        $this->notifier->handle($message->type, $message->eventId, $message->payload);
    }
}
