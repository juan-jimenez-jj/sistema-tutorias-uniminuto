<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/** Cabeceras de endurecimiento recomendadas por OWASP Secure Headers Project. */
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly bool $hsts = false)
    {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $response = $handler->handle($request)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'no-referrer')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->withHeader('Cross-Origin-Opener-Policy', 'same-origin');

        if (!$response->hasHeader('Content-Security-Policy')) {
            $response = $response->withHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");
        }
        if (!$response->hasHeader('Cache-Control')) {
            $response = $response->withHeader('Cache-Control', 'no-store');
        }
        if ($this->hsts) {
            $response = $response->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
