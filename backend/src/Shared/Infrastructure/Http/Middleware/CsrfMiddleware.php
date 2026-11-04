<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Tutorias\Shared\Domain\Forbidden;
use Tutorias\Shared\Infrastructure\Http\RequestContext;
use Tutorias\Shared\Infrastructure\Security\SessionCookies;

/**
 * Protección CSRF con el patrón "double submit cookie": en métodos que modifican
 * estado, si la sesión viaja en cookie, el header X-CSRF-Token debe coincidir con la
 * cookie legible tut_csrf. Un sitio externo no puede leer esa cookie ni fijar el header.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function process(Request $request, Handler $handler): Response
    {
        $viaCookie = $request->getAttribute(RequestContext::AUTH_VIA_COOKIE, false) === true;
        if ($viaCookie && !in_array(strtoupper($request->getMethod()), self::SAFE_METHODS, true)) {
            $cookie = $request->getCookieParams()[SessionCookies::CSRF] ?? '';
            $header = $request->getHeaderLine('X-CSRF-Token');
            if (!is_string($cookie) || $cookie === '' || !hash_equals($cookie, $header)) {
                throw Forbidden::because('Token CSRF ausente o inválido. Recarga la página e intenta de nuevo.');
            }
        }

        return $handler->handle($request);
    }
}
