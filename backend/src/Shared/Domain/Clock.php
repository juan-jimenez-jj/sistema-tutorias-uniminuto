<?php

declare(strict_types=1);

namespace Tutorias\Shared\Domain;

/**
 * Puerto para obtener la hora actual. Inyectarlo (en lugar de usar `new DateTime()`)
 * hace que reglas temporales como "cancelar con 3 horas de anticipación" sean
 * deterministas en las pruebas.
 */
interface Clock
{
    public function now(): \DateTimeImmutable;
}
