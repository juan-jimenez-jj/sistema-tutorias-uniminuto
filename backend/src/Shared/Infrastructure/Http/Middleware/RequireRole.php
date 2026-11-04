<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Tutorias\Shared\Domain\Role;
use Tutorias\Shared\Infrastructure\Http\RequestContext;

/** Control de acceso basado en roles (RBAC) aplicado a grupos de rutas. */
final class RequireRole implements MiddlewareInterface
{
    /** @var list<Role> */
    private readonly array $roles;

    public function __construct(Role ...$roles)
    {
        $this->roles = array_values($roles);
    }

    public function process(Request $request, Handler $handler): Response
    {
        RequestContext::actor($request)->mustBe(...$this->roles);

        return $handler->handle($request);
    }
}
