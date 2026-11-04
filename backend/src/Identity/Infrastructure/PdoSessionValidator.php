<?php

declare(strict_types=1);

namespace Tutorias\Identity\Infrastructure;

use Tutorias\Shared\Application\SessionValidator;

final class PdoSessionValidator implements SessionValidator
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function isActive(int $userId): bool
    {
        // Búsqueda por clave primaria: costo despreciable frente al beneficio de revocar al instante.
        $stmt = $this->pdo->prepare("SELECT 1 FROM usuarios WHERE id = ? AND estado = 'activo'");
        $stmt->execute([$userId]);

        return (bool) $stmt->fetchColumn();
    }
}
