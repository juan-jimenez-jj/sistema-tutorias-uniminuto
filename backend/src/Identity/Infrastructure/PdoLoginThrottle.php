<?php

declare(strict_types=1);

namespace Tutorias\Identity\Infrastructure;

use Tutorias\Identity\Domain\LoginThrottle;
use Tutorias\Shared\Domain\Clock;

/**
 * Máximo N intentos fallidos por correo (o 4N por IP) en una ventana deslizante.
 * Los intentos exitosos "limpian" el contador del correo.
 */
final class PdoLoginThrottle implements LoginThrottle
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly Clock $clock,
        private readonly int $maxAttempts = 5,
        private readonly int $windowSeconds = 900,
    ) {
    }

    public function secondsUntilAllowed(string $email, ?string $ip): int
    {
        $since = $this->clock->now()->modify("-{$this->windowSeconds} seconds")->format('Y-m-d H:i:s');

        $lastSuccess = $this->pdo->prepare('SELECT MAX(creado_en) FROM intentos_login WHERE email = ? AND exitoso = 1');
        $lastSuccess->execute([$email]);
        $from = max($since, (string) ($lastSuccess->fetchColumn() ?: $since));

        $byEmail = $this->pdo->prepare('SELECT COUNT(*), MIN(creado_en) FROM intentos_login WHERE email = ? AND exitoso = 0 AND creado_en > ?');
        $byEmail->execute([$email, $from]);
        [$count, $oldest] = $byEmail->fetch(\PDO::FETCH_NUM);

        if ((int) $count < $this->maxAttempts && $ip !== null) {
            $byIp = $this->pdo->prepare('SELECT COUNT(*), MIN(creado_en) FROM intentos_login WHERE ip = ? AND exitoso = 0 AND creado_en > ?');
            $byIp->execute([$ip, $since]);
            [$ipCount, $ipOldest] = $byIp->fetch(\PDO::FETCH_NUM);
            if ((int) $ipCount >= $this->maxAttempts * 4) {
                [$count, $oldest] = [$this->maxAttempts, $ipOldest];
            }
        }

        if ((int) $count < $this->maxAttempts || $oldest === null) {
            return 0;
        }
        $unlockAt = (new \DateTimeImmutable((string) $oldest, $this->clock->now()->getTimezone()))->modify("+{$this->windowSeconds} seconds");

        return max(1, $unlockAt->getTimestamp() - $this->clock->now()->getTimestamp());
    }

    public function registerAttempt(string $email, ?string $ip, bool $success): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO intentos_login (email, ip, exitoso, creado_en) VALUES (?, ?, ?, ?)');
        $stmt->execute([mb_substr($email, 0, 120), $ip, $success ? 1 : 0, $this->clock->now()->format('Y-m-d H:i:s')]);
    }
}
