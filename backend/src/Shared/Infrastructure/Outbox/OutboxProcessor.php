<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Outbox;

use Psr\Log\LoggerInterface;
use Tutorias\Shared\Domain\Clock;

/**
 * Procesa los eventos pendientes de la outbox con reintentos y backoff exponencial.
 * Usa SELECT ... FOR UPDATE SKIP LOCKED para que varios workers puedan correr en
 * paralelo sin procesar dos veces el mismo evento (escalabilidad horizontal).
 */
final class OutboxProcessor
{
    /** @var array<string, list<OutboxHandler>> */
    private array $handlers = [];

    private ?bool $skipLocked = null;

    /** @param iterable<OutboxHandler> $handlers */
    public function __construct(
        private readonly \PDO $pdo,
        private readonly Clock $clock,
        private readonly LoggerInterface $logger,
        iterable $handlers,
        private readonly int $maxAttempts = 5,
    ) {
        foreach ($handlers as $handler) {
            foreach ($handler->subscribedTo() as $type) {
                $this->handlers[$type][] = $handler;
            }
        }
    }

    /** @return array{procesados: int, fallidos: int} */
    public function processBatch(int $limit = 20): array
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');
        $skipLocked = $this->supportsSkipLocked() ? ' SKIP LOCKED' : '';
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, evento_id, tipo, payload, intentos FROM outbox_eventos
                 WHERE estado = 'pendiente' AND disponible_desde <= :now
                 ORDER BY id LIMIT {$limit} FOR UPDATE{$skipLocked}"
            );
            $stmt->execute(['now' => $now]);
            $rows = $stmt->fetchAll();
            if ($rows !== []) {
                $ids = implode(',', array_map(static fn (array $r) => (int) $r['id'], $rows));
                $this->pdo->exec("UPDATE outbox_eventos SET estado = 'procesando' WHERE id IN ({$ids})");
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        $processed = 0;
        $failed = 0;
        foreach ($rows as $row) {
            $message = new OutboxMessage(
                (int) $row['id'],
                (string) $row['evento_id'],
                (string) $row['tipo'],
                (array) json_decode((string) $row['payload'], true, 512, JSON_THROW_ON_ERROR),
                (int) $row['intentos'],
            );
            try {
                foreach ($this->handlers[$message->type] ?? [] as $handler) {
                    $handler->handle($message);
                }
                $this->markProcessed($message);
                $processed++;
            } catch (\Throwable $e) {
                $this->markFailed($message, $e);
                $failed++;
            }
        }

        return ['procesados' => $processed, 'fallidos' => $failed];
    }

    /**
     * SKIP LOCKED existe en MySQL 8+ y MariaDB 10.6+. Con MariaDB 10.4 (la de XAMPP) se usa
     * FOR UPDATE simple: funciona igual con un solo worker, que es el caso en desarrollo.
     */
    private function supportsSkipLocked(): bool
    {
        if ($this->skipLocked === null) {
            $version = (string) $this->pdo->query('SELECT VERSION()')->fetchColumn();
            preg_match('/^(\d+)\.(\d+)/', $version, $m);
            $numeric = ((int) ($m[1] ?? 0)) * 100 + (int) ($m[2] ?? 0);
            $this->skipLocked = stripos($version, 'mariadb') !== false ? $numeric >= 1006 : $numeric >= 800;
        }

        return $this->skipLocked;
    }

    private function markProcessed(OutboxMessage $message): void
    {
        $stmt = $this->pdo->prepare("UPDATE outbox_eventos SET estado = 'procesado', procesado_en = :now, intentos = intentos + 1, ultimo_error = NULL WHERE id = :id");
        $stmt->execute(['now' => $this->clock->now()->format('Y-m-d H:i:s'), 'id' => $message->id]);
        $this->logger->info('outbox.evento_procesado', ['evento' => $message->type, 'evento_id' => $message->eventId]);
    }

    private function markFailed(OutboxMessage $message, \Throwable $error): void
    {
        $attempts = $message->attempts + 1;
        $exhausted = $attempts >= $this->maxAttempts;
        // Backoff exponencial: 30s, 60s, 120s, 240s...
        $retryAt = $this->clock->now()->modify('+' . (30 * (2 ** ($attempts - 1))) . ' seconds');
        $stmt = $this->pdo->prepare(
            'UPDATE outbox_eventos SET estado = :estado, intentos = :intentos, disponible_desde = :retry, ultimo_error = :error WHERE id = :id'
        );
        $stmt->execute([
            'estado' => $exhausted ? 'fallido' : 'pendiente',
            'intentos' => $attempts,
            'retry' => $retryAt->format('Y-m-d H:i:s'),
            'error' => mb_substr($error->getMessage(), 0, 1000),
            'id' => $message->id,
        ]);
        $this->logger->warning('outbox.evento_fallido', [
            'evento' => $message->type,
            'evento_id' => $message->eventId,
            'intento' => $attempts,
            'agotado' => $exhausted,
            'error' => $error->getMessage(),
        ]);
    }
}
