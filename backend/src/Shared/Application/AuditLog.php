<?php

declare(strict_types=1);

namespace Tutorias\Shared\Application;

interface AuditLog
{
    /** @param array<string, mixed> $details */
    public function record(?int $actorId, string $action, string $entity, int|string|null $entityId, array $details = []): void;
}
