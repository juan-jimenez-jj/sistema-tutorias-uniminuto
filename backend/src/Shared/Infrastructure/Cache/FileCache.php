<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Cache;

use Tutorias\Shared\Application\Cache;

/**
 * Caché en disco (táctica de rendimiento para consultas frecuentes como el catálogo
 * de materias). Detrás del puerto Cache, por lo que puede reemplazarse por Redis
 * sin tocar los casos de uso.
 */
final class FileCache implements Cache
{
    public function __construct(private readonly string $directory)
    {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0775, true);
        }
    }

    public function remember(string $key, int $ttlSeconds, callable $compute): mixed
    {
        $file = $this->path($key);
        if (is_file($file)) {
            $data = @unserialize((string) file_get_contents($file));
            if (is_array($data) && ($data['expires'] ?? 0) > time()) {
                return $data['value'];
            }
        }
        $value = $compute();
        @file_put_contents($file, serialize(['expires' => time() + $ttlSeconds, 'value' => $value]), LOCK_EX);

        return $value;
    }

    public function forget(string $key): void
    {
        @unlink($this->path($key));
    }

    public function forgetPrefix(string $prefix): void
    {
        foreach (glob($this->directory . '/' . $this->safe($prefix) . '*.cache') ?: [] as $file) {
            @unlink($file);
        }
    }

    private function path(string $key): string
    {
        return $this->directory . '/' . $this->safe($key) . '.cache';
    }

    private function safe(string $key): string
    {
        return (string) preg_replace('/[^a-zA-Z0-9_.-]/', '_', $key);
    }
}
