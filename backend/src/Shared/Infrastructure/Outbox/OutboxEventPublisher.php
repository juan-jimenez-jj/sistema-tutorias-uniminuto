<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Outbox;

use Tutorias\Shared\Application\EventPublisher;
use Tutorias\Shared\Domain\DomainEvent;

/**
 * Patrón Transactional Outbox: el evento se inserta en la tabla outbox_eventos usando
 * la misma conexión PDO (y por tanto la misma transacción) que el caso de uso.
 * Un worker independiente (bin/console outbox:work) lo procesa después.
 */
final class OutboxEventPublisher implements EventPublisher
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function publish(DomainEvent ...$events): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO outbox_eventos (evento_id, tipo, payload, ocurrido_en, disponible_desde)
             VALUES (:id, :tipo, :payload, :ocurrido, :disponible)'
        );
        foreach ($events as $event) {
            $stmt->execute([
                'id' => bin2hex(random_bytes(16)),
                'tipo' => $event->eventName(),
                'payload' => json_encode($event->payload(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'ocurrido' => $event->occurredAt()->format('Y-m-d H:i:s'),
                'disponible' => $event->occurredAt()->format('Y-m-d H:i:s'),
            ]);
        }
    }
}
