<?php

declare(strict_types=1);

namespace Tutorias\Shared\Domain;

final class Forbidden extends DomainError
{
    public static function because(string $message): self
    {
        return new self($message, 'forbidden');
    }
}
