<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Persistence;

use Tutorias\Shared\Application\AuditLog;
use Tutorias\Shared\Domain\Clock;

final class PdoAuditLog implements AuditLog
{
    private ?string $ip = null;

    public function __construct(private readonly \PDO $pdo, private readonly Clock $clock)
    {
    }

    public function setClientIp(?string $ip): void
    {
        $this->ip = $ip;
    }

    public function record(?int $actorId, string $action, string $entity, int|string|null $entityId, array $details = []): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalle, ip, creado_en)
             VALUES (:usuario, :accion, :entidad, :entidad_id, :detalle, :ip, :creado)'
        );
        $stmt->execute([
            'usuario' => $actorId,
            'accion' => $action,
            'entidad' => $entity,
            'entidad_id' => $entityId === null ? null : (string) $entityId,
            'detalle' => $details === [] ? null : json_encode($details, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'ip' => $this->ip,
            'creado' => $this->clock->now()->format('Y-m-d H:i:s'),
        ]);
    }
}
