<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Outbox;

final readonly class OutboxMessage
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public int $id,
        public string $eventId,
        public string $type,
        public array $payload,
        public int $attempts,
    ) {
    }
}
