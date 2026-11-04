<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure;

/**
 * Configuración tomada de variables de entorno (12-factor). En desarrollo se puede
 * usar un archivo .env, que se carga con loadDotEnv() sin dependencias externas.
 */
final class Config
{
    /** @param array<string, string> $values */
    private function __construct(private readonly array $values)
    {
    }

    public static function fromEnvironment(?string $dotEnvPath = null): self
    {
        $values = [];
        if ($dotEnvPath !== null && is_file($dotEnvPath)) {
            $values = self::parseDotEnv((string) file_get_contents($dotEnvPath));
        }
        foreach (array_merge($_ENV, getenv()) as $key => $value) {
            if (is_string($value)) {
                $values[(string) $key] = $value; // el entorno real tiene prioridad sobre .env
            }
        }

        return new self(self::withCloudDefaults($values));
    }

    /**
     * Plataformas en la nube (Railway, Render…) entregan la base de datos como una sola URL
     * (mysql://usuario:clave@host:puerto/base). Si existe y no hay DB_* explícitas, se usa.
     * Si no se definió JWT_SECRET, se deriva uno estable de esa URL (que ya es secreta).
     *
     * @param array<string, string> $values
     * @return array<string, string>
     */
    private static function withCloudDefaults(array $values): array
    {
        $url = $values['MYSQL_URL'] ?? $values['DATABASE_URL'] ?? '';
        if ($url !== '' && empty($values['DB_HOST'])) {
            $parts = parse_url($url);
            if (is_array($parts) && isset($parts['host'])) {
                $values['DB_HOST'] = $parts['host'];
                $values['DB_PORT'] = (string) ($parts['port'] ?? 3306);
                $values['DB_USERNAME'] = rawurldecode($parts['user'] ?? '');
                $values['DB_PASSWORD'] = rawurldecode($parts['pass'] ?? '');
                $values['DB_DATABASE'] = ltrim($parts['path'] ?? '/tutorias', '/');
            }
        }
        if (empty($values['JWT_SECRET']) && $url !== '') {
            $values['JWT_SECRET'] = hash('sha256', 'tutorias-jwt|' . $url);
        }

        return $values;
    }

    /** @param array<string, string> $overrides */
    public function with(array $overrides): self
    {
        return new self(array_merge($this->values, $overrides));
    }

    public function string(string $key, string $default = ''): string
    {
        return $this->values[$key] ?? $default;
    }

    public function int(string $key, int $default = 0): int
    {
        return isset($this->values[$key]) ? (int) $this->values[$key] : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        if (!isset($this->values[$key])) {
            return $default;
        }

        return in_array(strtolower($this->values[$key]), ['1', 'true', 'yes', 'on'], true);
    }

    public function isProduction(): bool
    {
        return $this->string('APP_ENV', 'production') === 'production';
    }

    /** @return array<string, string> */
    private static function parseDotEnv(string $contents): array
    {
        $values = [];
        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $values[$key] = trim($value, "\"'");
        }

        return $values;
    }
}
