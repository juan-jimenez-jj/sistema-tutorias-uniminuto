<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Cache;

use Tutorias\Shared\Application\Cache;

final class NullCache implements Cache
{
    public function remember(string $key, int $ttlSeconds, callable $compute): mixed
    {
        return $compute();
    }

    public function forget(string $key): void
    {
    }

    public function forgetPrefix(string $prefix): void
    {
    }
}
