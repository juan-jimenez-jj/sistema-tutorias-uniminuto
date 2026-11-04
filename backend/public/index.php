<?php

declare(strict_types=1);

use Tutorias\Kernel;
use Tutorias\Shared\Infrastructure\Config;

require __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('America/Bogota');

$config = Config::fromEnvironment(__DIR__ . '/../.env');
Kernel::app(Kernel::container($config))->run();
