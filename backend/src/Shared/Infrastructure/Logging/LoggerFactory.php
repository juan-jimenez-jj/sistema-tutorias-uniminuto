<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;
use Tutorias\Shared\Infrastructure\Config;

/** Logs estructurados en JSON hacia stdout (recolectables por Docker / Loki / ELK). */
final class LoggerFactory
{
    public static function create(Config $config, string $channel = 'api'): LoggerInterface
    {
        $logger = new Logger($channel);
        $stream = $config->string('LOG_STREAM', 'php://stderr');
        $handler = new StreamHandler($stream, Level::fromName(ucfirst(strtolower($config->string('LOG_LEVEL', 'info')))));
        $handler->setFormatter(new JsonFormatter());
        $logger->pushHandler($handler);
        $logger->pushProcessor(new PsrLogMessageProcessor());
        $logger->pushProcessor(new RequestIdProcessor());

        return $logger;
    }
}
