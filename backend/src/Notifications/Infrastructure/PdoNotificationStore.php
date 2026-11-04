<?php

declare(strict_types=1);

namespace Tutorias\Notifications\Infrastructure;

use Tutorias\Notifications\Application\NotificationStore;
use Tutorias\Shared\Domain\Clock;

final class PdoNotificationStore implements NotificationStore
{
    public function __construct(private readonly \PDO $pdo, private readonly Clock $clock)
    {
    }

    public function add(int $userId, string $type, string $title, string $message, ?string $link, string $sourceEventId): void
    {
        // INSERT IGNORE + índice único (evento, usuario) = consumidor idempotente.
        $this->pdo->prepare(
            'INSERT IGNORE INTO notificaciones (usuario_id, tipo, titulo, mensaje, enlace, origen_evento_id, creado_en) VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$userId, $type, mb_substr($title, 0, 150), mb_substr($message, 0, 500), $link, $sourceEventId, $this->clock->now()->format('Y-m-d H:i:s')]);
    }

    public function forUser(int $userId, int $limit): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->pdo->prepare("SELECT id, tipo, titulo, mensaje, enlace, leida_en, creado_en FROM notificaciones WHERE usuario_id = ? ORDER BY creado_en DESC, id DESC LIMIT {$limit}");
        $stmt->execute([$userId]);
        $unread = $this->pdo->prepare('SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida_en IS NULL');
        $unread->execute([$userId]);

        return [
            'items' => array_map(static fn (array $r) => [
                'id' => (int) $r['id'],
                'tipo' => $r['tipo'],
                'titulo' => $r['titulo'],
                'mensaje' => $r['mensaje'],
                'enlace' => $r['enlace'],
                'leida' => $r['leida_en'] !== null,
                'creadoEn' => $r['creado_en'],
            ], $stmt->fetchAll()),
            'noLeidas' => (int) $unread->fetchColumn(),
        ];
    }

    public function markRead(int $userId, int $notificationId): void
    {
        $this->pdo->prepare('UPDATE notificaciones SET leida_en = ? WHERE id = ? AND usuario_id = ? AND leida_en IS NULL')
            ->execute([$this->clock->now()->format('Y-m-d H:i:s'), $notificationId, $userId]);
    }

    public function markAllRead(int $userId): void
    {
        $this->pdo->prepare('UPDATE notificaciones SET leida_en = ? WHERE usuario_id = ? AND leida_en IS NULL')
            ->execute([$this->clock->now()->format('Y-m-d H:i:s'), $userId]);
    }
}
