<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Tutorias\Shared\Infrastructure\Http\RequestContext;
use Tutorias\Shared\Infrastructure\Persistence\PdoAuditLog;

/** Entrega a la bitácora de auditoría la IP de origen de cada petición. */
final class AuditContextMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly PdoAuditLog $audit)
    {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $this->audit->setClientIp(RequestContext::clientIp($request));

        return $handler->handle($request);
    }
}
