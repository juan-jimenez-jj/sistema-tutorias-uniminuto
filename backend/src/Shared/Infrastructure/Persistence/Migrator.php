<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Persistence;

/**
 * Migrador minimalista: ejecuta en orden los archivos VNNN__descripcion.sql que aún
 * no estén registrados en la tabla schema_migrations. Cada migración es inmutable
 * una vez aplicada (se guarda su checksum para detectar modificaciones).
 */
final class Migrator
{
    public function __construct(private readonly \PDO $pdo, private readonly string $directory)
    {
    }

    /** @return list<string> migraciones aplicadas en esta ejecución */
    public function migrate(?callable $output = null): array
    {
        $output ??= static fn (string $line) => null;
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(20) PRIMARY KEY,
                descripcion VARCHAR(255) NOT NULL,
                checksum CHAR(64) NOT NULL,
                aplicada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $applied = [];
        foreach ($this->pdo->query('SELECT version, checksum FROM schema_migrations')->fetchAll() as $row) {
            $applied[$row['version']] = $row['checksum'];
        }

        $files = glob(rtrim($this->directory, '/') . '/V*__*.sql') ?: [];
        sort($files, SORT_NATURAL);

        $ran = [];
        foreach ($files as $file) {
            if (!preg_match('/(V\d+)__(.+)\.sql$/', basename($file), $m)) {
                continue;
            }
            [$version, $description] = [$m[1], str_replace('_', ' ', $m[2])];
            $sql = (string) file_get_contents($file);
            $checksum = hash('sha256', $sql);

            if (isset($applied[$version])) {
                if ($applied[$version] !== $checksum) {
                    throw new \RuntimeException("La migración {$version} fue modificada después de aplicarse. Crea una nueva migración en su lugar.");
                }
                continue;
            }

            $output("→ Aplicando {$version}: {$description}");
            foreach (self::splitStatements($sql) as $statement) {
                $this->pdo->exec($statement);
            }
            $stmt = $this->pdo->prepare('INSERT INTO schema_migrations (version, descripcion, checksum) VALUES (?, ?, ?)');
            $stmt->execute([$version, $description, $checksum]);
            $ran[] = $version;
        }

        return $ran;
    }

    /** @return list<string> */
    private static function splitStatements(string $sql): array
    {
        $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);
        $statements = [];
        $buffer = '';
        $inString = false;
        $quote = '';
        $length = strlen($sql);
        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            if ($inString) {
                if ($char === '\\') {
                    $buffer .= $char . ($sql[++$i] ?? '');
                    continue;
                }
                if ($char === $quote) {
                    $inString = false;
                }
            } elseif ($char === "'" || $char === '"' || $char === '`') {
                $inString = true;
                $quote = $char;
            } elseif ($char === ';') {
                if (trim($buffer) !== '') {
                    $statements[] = trim($buffer);
                }
                $buffer = '';
                continue;
            }
            $buffer .= $char;
        }
        if (trim($buffer) !== '') {
            $statements[] = trim($buffer);
        }

        return $statements;
    }
}
