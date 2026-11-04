<?php

declare(strict_types=1);

namespace Tutorias\Identity\Domain;

enum UserStatus: string
{
    case Activo = 'activo';
    case Inactivo = 'inactivo';
}
