<?php

declare(strict_types=1);

namespace Tutorias\Shared\Application;

use Tutorias\Shared\Domain\DomainEvent;

/**
 * Puerto de publicación de eventos. La implementación por defecto escribe en la
 * tabla outbox dentro de la MISMA transacción del caso de uso (patrón Transactional Outbox),
 * garantizando que no se pierdan notificaciones aunque el correo falle.
 */
interface EventPublisher
{
    public function publish(DomainEvent ...$events): void;
}
