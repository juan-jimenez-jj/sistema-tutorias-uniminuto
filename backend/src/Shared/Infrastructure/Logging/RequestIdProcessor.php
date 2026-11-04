<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/** Añade el identificador de la petición a cada línea de log (trazabilidad). */
final class RequestIdProcessor implements ProcessorInterface
{
    private static ?string $requestId = null;

    public static function set(?string $requestId): void
    {
        self::$requestId = $requestId;
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if (self::$requestId === null) {
            return $record;
        }

        return $record->with(extra: $record->extra + ['request_id' => self::$requestId]);
    }
}
