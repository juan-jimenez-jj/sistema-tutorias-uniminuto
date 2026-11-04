<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Http;

use Psr\Http\Message\ServerRequestInterface as Request;
use Tutorias\Shared\Application\Actor;
use Tutorias\Shared\Domain\Unauthorized;

final class RequestContext
{
    public const ACTOR = 'actor';
    public const REQUEST_ID = 'request_id';
    public const AUTH_VIA_COOKIE = 'auth_via_cookie';

    public static function actor(Request $request): Actor
    {
        $actor = $request->getAttribute(self::ACTOR);
        if (!$actor instanceof Actor) {
            throw Unauthorized::because();
        }

        return $actor;
    }

    public static function clientIp(Request $request): ?string
    {
        $server = $request->getServerParams();
        // Detrás de nginx (Docker) la IP real llega en X-Real-IP.
        $ip = $request->getHeaderLine('X-Real-IP') ?: ($server['REMOTE_ADDR'] ?? null);

        return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }

    public static function intArg(array $args, string $name): int
    {
        return (int) ($args[$name] ?? 0);
    }
}
