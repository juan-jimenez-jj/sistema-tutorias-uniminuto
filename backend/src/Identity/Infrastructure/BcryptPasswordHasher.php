<?php

declare(strict_types=1);

namespace Tutorias\Identity\Infrastructure;

use Tutorias\Identity\Domain\PasswordHasher;

final class BcryptPasswordHasher implements PasswordHasher
{
    public function __construct(private readonly int $cost = 12)
    {
    }

    public function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => $this->cost]);
    }

    public function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => $this->cost]);
    }
}
