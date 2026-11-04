<?php

declare(strict_types=1);

namespace Tutorias\Identity\Application;

use Tutorias\Identity\Domain\InstitutionalEmail;
use Tutorias\Identity\Domain\LoginThrottle;
use Tutorias\Identity\Domain\PasswordHasher;
use Tutorias\Identity\Domain\User;
use Tutorias\Identity\Domain\UserRepository;
use Tutorias\Shared\Application\AuditLog;
use Tutorias\Shared\Domain\Clock;
use Tutorias\Shared\Domain\TooManyRequests;
use Tutorias\Shared\Domain\Unauthorized;

/**
 * Corrige la vulnerabilidad crítica del sistema heredado, donde Usuario::login()
 * concatenaba el correo en el SQL (inyección) y nunca verificaba la contraseña.
 */
final class AuthenticateUser
{
    // Hash ficticio para igualar el tiempo de respuesta cuando el correo no existe
    // (evita enumerar usuarios midiendo latencia).
    private const DUMMY_HASH = '$2y$12$SDQ9XUHC0e3A2w4N.0QH0etal3gL4H3jeq4KkV3lJBxHFrbij0MwS';

    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly LoginThrottle $throttle,
        private readonly Clock $clock,
        private readonly AuditLog $audit,
    ) {
    }

    public function execute(string $email, string $password, ?string $ip): User
    {
        $normalized = mb_strtolower(trim($email));
        $wait = $this->throttle->secondsUntilAllowed($normalized, $ip);
        if ($wait > 0) {
            throw TooManyRequests::retryAfter($wait, 'Demasiados intentos fallidos. Intenta de nuevo en ' . (int) ceil($wait / 60) . ' minuto(s).');
        }

        $user = filter_var($normalized, FILTER_VALIDATE_EMAIL) ? $this->users->findByEmail(InstitutionalEmail::trusted($normalized)) : null;
        $valid = $this->hasher->verify($password, $user?->passwordHash() ?? self::DUMMY_HASH);

        if ($user === null || !$valid || !$user->isActive()) {
            $this->throttle->registerAttempt($normalized, $ip, false);
            $this->audit->record($user?->id(), 'auth.login_fallido', 'usuario', $user?->id(), ['email' => $normalized]);
            // Mensaje genérico: no revela si el correo existe o si la cuenta está inactiva.
            throw Unauthorized::because('Correo o contraseña incorrectos.');
        }

        if ($this->hasher->needsRehash($user->passwordHash())) {
            $user->changePasswordHash($this->hasher->hash($password));
        }
        $user->recordLogin($this->clock->now());
        $this->users->save($user);
        $this->throttle->registerAttempt($normalized, $ip, true);
        $this->audit->record($user->id(), 'auth.login', 'usuario', $user->id());

        return $user;
    }
}
