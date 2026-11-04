<?php

declare(strict_types=1);

namespace Tutorias\Shared\Domain;

/**
 * Se lanza cuando una operación viola una regla de negocio
 * (p. ej. cancelar con menos de 3 horas de anticipación).
 */
final class BusinessRuleViolation extends DomainError
{
    /** @param array<string, mixed> $context */
    public static function rule(string $code, string $message, array $context = []): self
    {
        return new self($message, $code, $context);
    }
}
