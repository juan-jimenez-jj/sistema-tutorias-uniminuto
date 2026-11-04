<?php

declare(strict_types=1);

namespace Tutorias\Identity\Domain;

interface PasswordHasher
{
    public function hash(string $plain): string;

    public function verify(string $plain, string $hash): bool;

    public function needsRehash(string $hash): bool;
}
