<?php

declare(strict_types=1);

namespace Tutorias\Shared\Domain;

final class TooManyRequests extends DomainError
{
    public static function retryAfter(int $seconds, string $message): self
    {
        return new self($message, 'too_many_requests', ['retry_after' => $seconds]);
    }
}
