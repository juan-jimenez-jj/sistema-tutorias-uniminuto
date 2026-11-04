<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Persistence;

use Tutorias\Shared\Infrastructure\Config;

final class ConnectionFactory
{
    public static function create(Config $config): \PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config->string('DB_HOST', '127.0.0.1'),
            $config->int('DB_PORT', 3306),
            $config->string('DB_DATABASE', 'tutorias'),
        );

        $pdo = new \PDO($dsn, $config->string('DB_USERNAME', 'tutorias'), $config->string('DB_PASSWORD', ''), [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            // Consultas preparadas reales en el servidor: defensa principal contra SQL Injection.
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
        $pdo->exec("SET time_zone = '-05:00'");
        $pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION'");

        return $pdo;
    }
}
