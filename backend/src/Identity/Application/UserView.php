<?php

declare(strict_types=1);

namespace Tutorias\Identity\Application;

use Tutorias\Identity\Domain\User;

/** DTO de salida: nunca expone el hash de la contraseña. */
final class UserView
{
    /** @return array<string, mixed> */
    public static function from(User $user): array
    {
        return [
            'id' => $user->id(),
            'idInstitucional' => $user->institutionalId(),
            'nombre' => $user->name(),
            'email' => $user->email()->value,
            'rol' => $user->role()->value,
            'programaAcademico' => $user->program(),
            'estado' => $user->status()->value,
            'ultimoAcceso' => $user->lastLoginAt()?->format(DATE_ATOM),
            'creadoEn' => $user->createdAt()->format(DATE_ATOM),
        ];
    }
}
