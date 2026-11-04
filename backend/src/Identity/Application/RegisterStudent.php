<?php

declare(strict_types=1);

namespace Tutorias\Identity\Application;

use Tutorias\Identity\Domain\InstitutionalEmail;
use Tutorias\Identity\Domain\PasswordHasher;
use Tutorias\Identity\Domain\PasswordPolicy;
use Tutorias\Identity\Domain\User;
use Tutorias\Identity\Domain\UserRepository;
use Tutorias\Shared\Application\AuditLog;
use Tutorias\Shared\Domain\Clock;
use Tutorias\Shared\Domain\Conflict;
use Tutorias\Shared\Domain\ValidationError;

final class RegisterStudent
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly Clock $clock,
        private readonly AuditLog $audit,
    ) {
    }

    public function execute(string $institutionalId, string $name, string $email, string $password, string $program, bool $acceptsDataPolicy): User
    {
        if (!$acceptsDataPolicy) {
            throw ValidationError::field('aceptaTratamientoDatos', 'Debes autorizar el tratamiento de datos personales (Ley 1581 de 2012).');
        }
        if (!preg_match('/^[A-Za-z0-9-]{4,20}$/', $institutionalId)) {
            throw ValidationError::field('idInstitucional', 'El ID institucional debe tener entre 4 y 20 caracteres alfanuméricos.');
        }
        $emailVo = InstitutionalEmail::fromString($email);
        PasswordPolicy::assertValid($password);

        if ($this->users->emailTaken($emailVo)) {
            throw Conflict::because('email_taken', 'Ya existe una cuenta con ese correo institucional.');
        }
        if ($this->users->institutionalIdTaken(strtoupper($institutionalId))) {
            throw Conflict::because('institutional_id_taken', 'Ya existe una cuenta con ese ID institucional.');
        }

        $user = User::registerStudent($institutionalId, $name, $emailVo, $this->hasher->hash($password), $program, $this->clock->now());
        $this->users->save($user);
        $this->audit->record($user->id(), 'usuario.registrado', 'usuario', $user->id(), ['rol' => 'estudiante']);

        return $user;
    }
}
