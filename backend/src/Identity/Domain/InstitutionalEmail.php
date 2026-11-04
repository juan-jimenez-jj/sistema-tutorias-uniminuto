<?php

declare(strict_types=1);

namespace Tutorias\Identity\Domain;

use Tutorias\Shared\Domain\ValidationError;

/** Value Object: correo institucional normalizado y validado. */
final readonly class InstitutionalEmail
{
    public const DOMAIN = 'uniminuto.edu.co';

    private function __construct(public string $value)
    {
    }

    public static function fromString(string $email): self
    {
        $normalized = mb_strtolower(trim($email));
        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationError::field('email', 'El correo electrónico no tiene un formato válido.');
        }
        if (!str_ends_with($normalized, '@' . self::DOMAIN)) {
            throw ValidationError::field('email', 'Debes usar tu correo institucional @' . self::DOMAIN . '.');
        }

        return new self($normalized);
    }

    /** Para datos ya persistidos (no se revalida el dominio institucional). */
    public static function trusted(string $email): self
    {
        return new self(mb_strtolower($email));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
