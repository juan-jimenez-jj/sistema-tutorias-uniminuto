<?php

declare(strict_types=1);

namespace Tutorias\Shared\Application;

interface Cache
{
    /**
     * @template T
     * @param callable(): T $compute
     * @return T
     */
    public function remember(string $key, int $ttlSeconds, callable $compute): mixed;

    public function forget(string $key): void;

    public function forgetPrefix(string $prefix): void;
}
