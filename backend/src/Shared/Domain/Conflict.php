<?php

declare(strict_types=1);

namespace Tutorias\Shared\Domain;

/** Conflicto con el estado actual del recurso (duplicados, cupo agotado, etc.). */
final class Conflict extends DomainError
{
    /** @param array<string, mixed> $context */
    public static function because(string $code, string $message, array $context = []): self
    {
        return new self($message, $code, $context);
    }
}
