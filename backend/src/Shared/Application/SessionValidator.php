<?php

declare(strict_types=1);

namespace Tutorias\Shared\Application;

/** Permite revocar sesiones de inmediato: un usuario desactivado deja de ser aceptado aunque su JWT no haya expirado. */
interface SessionValidator
{
    public function isActive(int $userId): bool;
}
