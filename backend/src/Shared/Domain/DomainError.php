<?php

declare(strict_types=1);

namespace Tutorias\Shared\Domain;

/**
 * Error base del dominio. Cada subclase se traduce a un código HTTP en la capa
 * de entrada (ver ErrorHandlerMiddleware), de modo que el dominio nunca conoce HTTP.
 */
abstract class DomainError extends \RuntimeException
{
    /** @param array<string, mixed> $context */
    public function __construct(
        string $message,
        private readonly string $errorCode,
        private readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return $this->context;
    }
}
