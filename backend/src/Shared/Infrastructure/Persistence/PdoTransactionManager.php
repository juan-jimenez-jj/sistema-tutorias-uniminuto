<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Persistence;

use Tutorias\Shared\Application\TransactionManager;

final class PdoTransactionManager implements TransactionManager
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function transactional(callable $operation): mixed
    {
        // Transacciones anidadas: si ya hay una abierta, se reutiliza.
        if ($this->pdo->inTransaction()) {
            return $operation();
        }

        $this->pdo->beginTransaction();
        try {
            $result = $operation();
            $this->pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->rollBackQuietly();
            throw $e;
        }
    }

    private function rollBackQuietly(): void
    {
        try {
            $this->pdo->rollBack();
        } catch (\PDOException) {
            // La transacción ya no estaba activa (p. ej. el servidor la abortó por un deadlock).
        }
    }
}
