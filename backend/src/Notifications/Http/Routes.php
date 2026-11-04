<?php

declare(strict_types=1);

namespace Tutorias\Notifications\Http;

use Slim\Routing\RouteCollectorProxy as Group;

final class Routes
{
    public static function authenticated(Group $api): void
    {
        $api->get('/notificaciones', [NotificationController::class, 'list']);
        $api->post('/notificaciones/{id:\d+}/lectura', [NotificationController::class, 'markRead']);
        $api->post('/notificaciones/lectura', [NotificationController::class, 'markAllRead']);
    }
}
