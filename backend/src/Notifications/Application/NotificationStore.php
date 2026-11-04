<?php

declare(strict_types=1);

namespace Tutorias\Notifications\Application;

interface NotificationStore
{
    /** Idempotente: el mismo evento no genera dos notificaciones para el mismo usuario. */
    public function add(int $userId, string $type, string $title, string $message, ?string $link, string $sourceEventId): void;

    /** @return array{items: list<array<string, mixed>>, noLeidas: int} */
    public function forUser(int $userId, int $limit): array;

    public function markRead(int $userId, int $notificationId): void;

    public function markAllRead(int $userId): void;
}
