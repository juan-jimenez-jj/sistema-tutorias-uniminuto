<?php

declare(strict_types=1);

namespace Tutorias\Reporting\Http;

use Slim\Routing\RouteCollectorProxy as Group;
use Tutorias\Shared\Domain\Role;
use Tutorias\Shared\Infrastructure\Http\Middleware\RequireRole;

final class Routes
{
    public static function authenticated(Group $api): void
    {
        $api->group('/admin', function (Group $g): void {
            $g->get('/reportes', [ReportController::class, 'dashboard']);
            $g->get('/reportes/tutorias.csv', [ReportController::class, 'exportCsv']);
            $g->get('/auditoria', [ReportController::class, 'audit']);
        })->add(new RequireRole(Role::Administrador));
    }
}
