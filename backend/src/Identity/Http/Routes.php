<?php

declare(strict_types=1);

namespace Tutorias\Identity\Http;

use Slim\Routing\RouteCollectorProxy as Group;
use Tutorias\Shared\Domain\Role;
use Tutorias\Shared\Infrastructure\Http\Middleware\RequireRole;

final class Routes
{
    public static function public(Group $api): void
    {
        $api->post('/auth/registro', [AuthController::class, 'register']);
        $api->post('/auth/login', [AuthController::class, 'login']);
        $api->post('/auth/logout', [AuthController::class, 'logout']);
        $api->get('/publico/resumen', \Tutorias\Platform\Http\PublicSummaryController::class);
    }

    public static function authenticated(Group $api): void
    {
        $api->get('/auth/yo', [AuthController::class, 'me']);
        $api->put('/auth/password', [AuthController::class, 'changePassword']);

        $api->group('/admin/usuarios', function (Group $g): void {
            $g->get('', [UserAdminController::class, 'list']);
            $g->post('', [UserAdminController::class, 'create']);
            $g->put('/{id:\d+}', [UserAdminController::class, 'update']);
            $g->patch('/{id:\d+}/estado', [UserAdminController::class, 'setStatus']);
            $g->put('/{id:\d+}/password', [UserAdminController::class, 'resetPassword']);
        })->add(new RequireRole(Role::Administrador));
    }
}
