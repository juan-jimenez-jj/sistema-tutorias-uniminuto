<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Security;

use Psr\Http\Message\ResponseInterface as Response;

final class SessionCookies
{
    public const SESSION = 'tut_session';
    public const CSRF = 'tut_csrf';

    public function __construct(private readonly bool $secure, private readonly int $ttlSeconds)
    {
    }

    public function attach(Response $response, string $token): Response
    {
        $csrf = bin2hex(random_bytes(32));

        return $response
            ->withAddedHeader('Set-Cookie', $this->cookie(self::SESSION, $token, $this->ttlSeconds, httpOnly: true))
            ->withAddedHeader('Set-Cookie', $this->cookie(self::CSRF, $csrf, $this->ttlSeconds, httpOnly: false));
    }

    public function clear(Response $response): Response
    {
        return $response
            ->withAddedHeader('Set-Cookie', $this->cookie(self::SESSION, '', -3600, httpOnly: true))
            ->withAddedHeader('Set-Cookie', $this->cookie(self::CSRF, '', -3600, httpOnly: false));
    }

    private function cookie(string $name, string $value, int $ttl, bool $httpOnly): string
    {
        $parts = [
            $name . '=' . rawurlencode($value),
            'Path=/',
            'Max-Age=' . max(0, $ttl),
            'SameSite=Strict',
        ];
        if ($ttl < 0) {
            $parts[] = 'Expires=Thu, 01 Jan 1970 00:00:00 GMT';
        }
        if ($httpOnly) {
            $parts[] = 'HttpOnly';
        }
        if ($this->secure) {
            $parts[] = 'Secure';
        }

        return implode('; ', $parts);
    }
}
