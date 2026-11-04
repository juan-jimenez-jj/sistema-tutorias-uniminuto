<?php

declare(strict_types=1);

namespace Tutorias\Booking\Http;

use Slim\Routing\RouteCollectorProxy as Group;
use Tutorias\Shared\Domain\Role;
use Tutorias\Shared\Infrastructure\Http\Middleware\RequireRole;

final class Routes
{
    public static function authenticated(Group $api): void
    {
        $api->get('/ofertas', [BookingController::class, 'offers'])->add(new RequireRole(Role::Estudiante));
        $api->post('/tutorias', [BookingController::class, 'create'])->add(new RequireRole(Role::Estudiante));
        $api->get('/tutorias', [BookingController::class, 'list']);
        $api->get('/tutorias/estadisticas', [BookingController::class, 'stats']);
        $api->get('/tutorias/{id:\d+}', [BookingController::class, 'show']);
        $api->post('/tutorias/{id:\d+}/cancelacion', [BookingController::class, 'cancel']);
        $api->post('/tutorias/{id:\d+}/cierre', [BookingController::class, 'close'])->add(new RequireRole(Role::Docente));
        $api->post('/tutorias/{id:\d+}/calificacion', [BookingController::class, 'rate'])->add(new RequireRole(Role::Estudiante));
    }
}
