<?php

declare(strict_types=1);

namespace Tutorias;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy as Group;
use Tutorias\Platform\Http\HealthController;
use Tutorias\Shared\Infrastructure\Config;
use Tutorias\Shared\Infrastructure\Container;
use Tutorias\Shared\Infrastructure\Http\Middleware\AuditContextMiddleware;
use Tutorias\Shared\Infrastructure\Http\Middleware\AuthenticationMiddleware;
use Tutorias\Shared\Infrastructure\Http\Middleware\CsrfMiddleware;
use Tutorias\Shared\Infrastructure\Http\Middleware\ErrorHandlerMiddleware;
use Tutorias\Shared\Infrastructure\Http\Middleware\RequestIdMiddleware;
use Tutorias\Shared\Infrastructure\Http\Middleware\SecurityHeadersMiddleware;

/**
 * Arranque de la aplicación HTTP. Cada módulo registra sus propias rutas
 * (Identity, Catalog, Scheduling, Booking, Notifications, Reporting).
 */
final class Kernel
{
    public const VERSION = '2.0.0';

    /** @var list<class-string> */
    private const MODULE_ROUTES = [
        Identity\Http\Routes::class,
        Catalog\Http\Routes::class,
        Scheduling\Http\Routes::class,
        Booking\Http\Routes::class,
        Notifications\Http\Routes::class,
        Reporting\Http\Routes::class,
    ];

    public static function container(Config $config): Container
    {
        $container = new Container();
        (require __DIR__ . '/../config/container.php')($container, $config);

        return $container;
    }

    /** @return App<\Psr\Container\ContainerInterface|null> */
    public static function app(Container $container): App
    {
        /** @var Config $config */
        $config = $container->get(Config::class);
        AppFactory::setContainer($container);
        $app = AppFactory::create();
        $container->instance(ResponseFactoryInterface::class, $app->getResponseFactory());

        $app->get('/health', [HealthController::class, 'live']);
        $app->get('/health/ready', [HealthController::class, 'ready']);
        $app->get('/metrics', [HealthController::class, 'metrics']);

        $app->group('/api/v1', function (Group $api) use ($container): void {
            foreach (self::MODULE_ROUTES as $routes) {
                if (method_exists($routes, 'public')) {
                    $routes::public($api);
                }
            }
            $api->group('', function (Group $protected): void {
                foreach (self::MODULE_ROUTES as $routes) {
                    if (method_exists($routes, 'authenticated')) {
                        $routes::authenticated($protected);
                    }
                }
            })
                ->add(new CsrfMiddleware())
                ->add($container->get(AuthenticationMiddleware::class));
        });

        // El orden importa: el último middleware agregado es el primero en ejecutarse.
        $app->add($container->get(AuditContextMiddleware::class));
        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        $app->add(new ErrorHandlerMiddleware($app->getResponseFactory(), $container->get(LoggerInterface::class), !$config->isProduction()));
        $app->add(new SecurityHeadersMiddleware($config->bool('APP_HSTS')));
        $app->add(new RequestIdMiddleware($container->get(LoggerInterface::class)));

        return $app;
    }
}
