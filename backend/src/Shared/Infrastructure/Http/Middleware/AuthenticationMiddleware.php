<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Tutorias\Shared\Application\SessionValidator;
use Tutorias\Shared\Domain\Unauthorized;
use Tutorias\Shared\Infrastructure\Http\RequestContext;
use Tutorias\Shared\Infrastructure\Security\SessionCookies;
use Tutorias\Shared\Infrastructure\Security\TokenService;

/**
 * Autentica la petición a partir de la cookie de sesión (navegador) o del header
 * Authorization: Bearer (clientes de API, Swagger UI, pruebas).
 */
final class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly TokenService $tokens, private readonly SessionValidator $sessions)
    {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $header = $request->getHeaderLine('Authorization');
        $viaCookie = false;
        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
            $token = $m[1];
        } else {
            $token = $request->getCookieParams()[SessionCookies::SESSION] ?? null;
            $viaCookie = true;
        }
        if (!is_string($token) || $token === '') {
            throw Unauthorized::because();
        }

        $actor = $this->tokens->verify($token);
        if (!$this->sessions->isActive($actor->id)) {
            throw Unauthorized::because('Tu cuenta fue desactivada. Comunícate con coordinación académica.');
        }

        return $handler->handle(
            $request->withAttribute(RequestContext::ACTOR, $actor)->withAttribute(RequestContext::AUTH_VIA_COOKIE, $viaCookie)
        );
    }
}
