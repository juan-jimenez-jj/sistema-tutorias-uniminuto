<?php

declare(strict_types=1);

namespace Tutorias\Catalog\Http;

use Slim\Routing\RouteCollectorProxy as Group;
use Tutorias\Shared\Domain\Role;
use Tutorias\Shared\Infrastructure\Http\Middleware\RequireRole;

final class Routes
{
    public static function authenticated(Group $api): void
    {
        $api->get('/materias', [CatalogController::class, 'subjects']);
        $api->get('/materias/{id:\d+}/docentes', [CatalogController::class, 'teachers']);

        $api->group('/admin', function (Group $g): void {
            $g->post('/materias', [CatalogController::class, 'create']);
            $g->put('/materias/{id:\d+}', [CatalogController::class, 'update']);
            $g->put('/docentes/{id:\d+}/materias', [CatalogController::class, 'assignToTeacher']);
        })->add(new RequireRole(Role::Administrador));
    }
}
