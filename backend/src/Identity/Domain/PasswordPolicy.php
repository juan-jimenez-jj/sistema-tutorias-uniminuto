<?php

declare(strict_types=1);

namespace Tutorias\Identity\Domain;

use Tutorias\Shared\Domain\ValidationError;

/**
 * Política de contraseñas. El requisito heredado era "mínimo 8 caracteres";
 * se endurece con letras y números y se limita a 72 bytes (límite de bcrypt).
 */
final class PasswordPolicy
{
    public static function assertValid(string $password, string $field = 'password'): void
    {
        if (strlen($password) < 8) {
            throw ValidationError::field($field, 'La contraseña debe tener al menos 8 caracteres.');
        }
        if (strlen($password) > 72) {
            throw ValidationError::field($field, 'La contraseña no puede superar 72 caracteres.');
        }
        if (!preg_match('/\p{L}/u', $password) || !preg_match('/\d/', $password)) {
            throw ValidationError::field($field, 'La contraseña debe combinar letras y números.');
        }
    }
}
