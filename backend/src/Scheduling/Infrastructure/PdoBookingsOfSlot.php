<?php

declare(strict_types=1);

namespace Tutorias\Scheduling\Infrastructure;

use Tutorias\Scheduling\Application\BookingsOfSlot;

final class PdoBookingsOfSlot implements BookingsOfSlot
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function futureScheduledCount(int $slotId, \DateTimeImmutable $now): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM tutorias WHERE franja_id = ? AND estado = 'programada' AND TIMESTAMP(fecha, hora_inicio) > ?"
        );
        $stmt->execute([$slotId, $now->format('Y-m-d H:i:s')]);

        return (int) $stmt->fetchColumn();
    }

    public function maxFutureScheduledPerDate(int $slotId, \DateTimeImmutable $now): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(MAX(c), 0) FROM (
                SELECT COUNT(*) AS c FROM tutorias WHERE franja_id = ? AND estado = 'programada' AND fecha >= ? GROUP BY fecha
             ) x"
        );
        $stmt->execute([$slotId, $now->format('Y-m-d')]);

        return (int) $stmt->fetchColumn();
    }
}
