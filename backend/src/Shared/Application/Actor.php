<?php

declare(strict_types=1);

namespace Tutorias\Shared\Application;

use Tutorias\Shared\Domain\Forbidden;
use Tutorias\Shared\Domain\Role;

/** Usuario autenticado que ejecuta un caso de uso. Lo construye la capa HTTP a partir del token. */
final readonly class Actor
{
    public function __construct(
        public int $id,
        public Role $role,
        public string $name,
        public string $email,
    ) {
    }

    public function is(Role $role): bool
    {
        return $this->role === $role;
    }

    public function mustBe(Role ...$roles): void
    {
        if (!in_array($this->role, $roles, true)) {
            throw Forbidden::because('Tu rol no tiene permiso para realizar esta acción.');
        }
    }
}
