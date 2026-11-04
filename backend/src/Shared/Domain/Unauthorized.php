<?php

declare(strict_types=1);

namespace Tutorias\Shared\Domain;

final class Unauthorized extends DomainError
{
    public static function because(string $message = 'Debes iniciar sesión.'): self
    {
        return new self($message, 'unauthorized');
    }
}
