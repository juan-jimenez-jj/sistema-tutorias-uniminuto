<?php

declare(strict_types=1);

namespace Tutorias\Scheduling\Http;

use Slim\Routing\RouteCollectorProxy as Group;
use Tutorias\Shared\Domain\Role;
use Tutorias\Shared\Infrastructure\Http\Middleware\RequireRole;

final class Routes
{
    public static function authenticated(Group $api): void
    {
        $api->group('/docente/franjas', function (Group $g): void {
            $g->get('', [AvailabilityController::class, 'mine']);
            $g->post('', [AvailabilityController::class, 'create']);
            $g->put('/{id:\d+}', [AvailabilityController::class, 'update']);
            $g->patch('/{id:\d+}/estado', [AvailabilityController::class, 'setActive']);
        })->add(new RequireRole(Role::Docente));
        $api->get('/docente/materias', [AvailabilityController::class, 'subjects'])->add(new RequireRole(Role::Docente));
    }
}
