<?php

declare(strict_types=1);

namespace Tutorias\Tests\Unit\Shared;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tutorias\Shared\Infrastructure\Config;

final class ConfigTest extends TestCase
{
    #[Test]
    public function toma_la_base_de_datos_de_mysql_url_en_la_nube(): void
    {
        putenv('MYSQL_URL=mysql://root:cl%40ve@mysql.railway.internal:3307/railway');
        putenv('DB_HOST');
        putenv('JWT_SECRET');
        try {
            $config = Config::fromEnvironment();
            self::assertSame('mysql.railway.internal', $config->string('DB_HOST'));
            self::assertSame(3307, $config->int('DB_PORT'));
            self::assertSame('cl@ve', $config->string('DB_PASSWORD'));
            self::assertSame('railway', $config->string('DB_DATABASE'));
            self::assertSame(64, strlen($config->string('JWT_SECRET')));
        } finally {
            putenv('MYSQL_URL');
        }
    }
}
