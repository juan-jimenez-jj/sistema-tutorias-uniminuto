<?php

declare(strict_types=1);

namespace Tutorias\Tests\Support;

use Tutorias\Kernel;
use Tutorias\Notifications\Application\Mailer;
use Tutorias\Notifications\Infrastructure\LogMailer;
use Tutorias\Shared\Domain\Clock;
use Tutorias\Shared\Infrastructure\Config;
use Tutorias\Shared\Infrastructure\Container;
use Tutorias\Shared\Infrastructure\Persistence\Migrator;

/** Construye el contenedor real apuntando a la base de datos de pruebas. */
final class TestApp
{
    private static bool $migrated = false;

    public static function config(): Config
    {
        return Config::fromEnvironment()->with([
            'APP_ENV' => 'testing',
            'DB_HOST' => (string) getenv('TEST_DB_HOST'),
            'DB_PORT' => (string) getenv('TEST_DB_PORT'),
            'DB_DATABASE' => (string) getenv('TEST_DB_DATABASE'),
            'DB_USERNAME' => (string) getenv('TEST_DB_USERNAME'),
            'DB_PASSWORD' => (string) getenv('TEST_DB_PASSWORD'),
            'JWT_SECRET' => 'test-secret-test-secret-test-secret-test-secret',
            'COOKIE_SECURE' => 'false',
            'BCRYPT_COST' => '4',
            'CACHE_ENABLED' => 'false',
            'MAIL_DRIVER' => 'log',
            'LOG_STREAM' => 'php://memory',
        ]);
    }

    public static function container(?FrozenClock $clock = null): Container
    {
        $container = Kernel::container(self::config());
        $container->instance(Clock::class, $clock ?? new FrozenClock());
        $container->instance(Mailer::class, new LogMailer());

        return $container;
    }

    /** Recrea el esquema una vez por ejecución de la suite y deja las tablas vacías. */
    public static function resetDatabase(\PDO $pdo): void
    {
        if (!self::$migrated) {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN) as $table) {
                $pdo->exec("DROP TABLE `{$table}`");
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            (new Migrator($pdo, __DIR__ . '/../../database/migrations'))->migrate();
            self::$migrated = true;
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['tutorias', 'franjas_disponibilidad', 'docente_materia', 'notificaciones', 'outbox_eventos', 'auditoria', 'intentos_login', 'usuarios'] as $table) {
            $pdo->exec("TRUNCATE TABLE {$table}");
        }
        $pdo->exec('DELETE FROM materias WHERE id > 24'); // las creadas por otras pruebas
        $pdo->exec("UPDATE materias SET estado = 'activa'");
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}
