<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Tutorias\Shared\Application\Actor;
use Tutorias\Shared\Domain\Clock;
use Tutorias\Shared\Domain\Role;
use Tutorias\Shared\Domain\Unauthorized;

/**
 * Emite y valida tokens JWT (HS256). El token viaja en una cookie HttpOnly
 * (inaccesible desde JavaScript → mitiga robo por XSS) o en el header Authorization.
 */
final class TokenService
{
    public function __construct(
        private readonly string $secret,
        private readonly Clock $clock,
        private readonly int $ttlSeconds = 28800,
        private readonly string $issuer = 'tutorias-uniminuto',
    ) {
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('JWT_SECRET debe tener al menos 32 caracteres.');
        }
    }

    public function issue(Actor $actor): string
    {
        $now = $this->clock->now()->getTimestamp();

        return JWT::encode([
            'iss' => $this->issuer,
            'sub' => (string) $actor->id,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->ttlSeconds,
            'jti' => bin2hex(random_bytes(8)),
            'rol' => $actor->role->value,
            'nom' => $actor->name,
            'email' => $actor->email,
        ], $this->secret, 'HS256');
    }

    public function verify(string $token): Actor
    {
        try {
            JWT::$timestamp = $this->clock->now()->getTimestamp();
            $claims = JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (\Throwable) {
            throw Unauthorized::because('Tu sesión expiró o no es válida. Inicia sesión nuevamente.');
        }
        if (($claims->iss ?? null) !== $this->issuer) {
            throw Unauthorized::because('Token emitido por un tercero no confiable.');
        }
        $role = Role::tryFrom((string) ($claims->rol ?? ''));
        if ($role === null) {
            throw Unauthorized::because();
        }

        return new Actor((int) $claims->sub, $role, (string) ($claims->nom ?? ''), (string) ($claims->email ?? ''));
    }

    public function ttlSeconds(): int
    {
        return $this->ttlSeconds;
    }
}
