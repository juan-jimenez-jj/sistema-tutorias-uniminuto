<?php

declare(strict_types=1);

namespace Tutorias\Shared\Domain;

/** Roles del sistema (RBAC). Viven en el kernel compartido porque todos los módulos los usan. */
enum Role: string
{
    case Estudiante = 'estudiante';
    case Docente = 'docente';
    case Administrador = 'administrador';

    public function label(): string
    {
        return match ($this) {
            self::Estudiante => 'Estudiante',
            self::Docente => 'Docente',
            self::Administrador => 'Administrador',
        };
    }
}
