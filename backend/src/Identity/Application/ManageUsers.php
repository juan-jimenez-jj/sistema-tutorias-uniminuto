<?php

declare(strict_types=1);

namespace Tutorias\Identity\Application;

use Tutorias\Identity\Domain\InstitutionalEmail;
use Tutorias\Identity\Domain\PasswordHasher;
use Tutorias\Identity\Domain\PasswordPolicy;
use Tutorias\Identity\Domain\User;
use Tutorias\Identity\Domain\UserRepository;
use Tutorias\Shared\Application\Actor;
use Tutorias\Shared\Application\AuditLog;
use Tutorias\Shared\Domain\Clock;
use Tutorias\Shared\Domain\Conflict;
use Tutorias\Shared\Domain\NotFound;
use Tutorias\Shared\Domain\Role;

/** Casos de uso de administración de usuarios (solo rol administrador). */
final class ManageUsers
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly Clock $clock,
        private readonly AuditLog $audit,
    ) {
    }

    public function create(Actor $admin, string $institutionalId, string $name, string $email, string $password, Role $role, ?string $program): User
    {
        $admin->mustBe(Role::Administrador);
        $emailVo = InstitutionalEmail::fromString($email);
        PasswordPolicy::assertValid($password);
        $this->assertUnique($emailVo, strtoupper($institutionalId));

        $user = User::createByAdministrator($institutionalId, $name, $emailVo, $this->hasher->hash($password), $role, $program, $this->clock->now());
        $this->users->save($user);
        $this->audit->record($admin->id, 'usuario.creado', 'usuario', $user->id(), ['rol' => $role->value]);

        return $user;
    }

    public function update(Actor $admin, int $userId, string $institutionalId, string $name, string $email, Role $role, ?string $program): User
    {
        $admin->mustBe(Role::Administrador);
        $user = $this->get($userId);
        $emailVo = InstitutionalEmail::fromString($email);
        $this->assertUnique($emailVo, strtoupper($institutionalId), $userId);

        $before = UserView::from($user);
        $user->updateProfile($name, $program);
        $user->changeEmail($emailVo);
        if ($user->role() !== $role) {
            if ($userId === $admin->id) {
                throw Conflict::because('cannot_change_own_role', 'No puedes cambiar tu propio rol.');
            }
            $user->changeRole($role);
        }
        $this->users->save($user);
        $this->audit->record($admin->id, 'usuario.actualizado', 'usuario', $userId, ['antes' => $before, 'despues' => UserView::from($user)]);

        return $user;
    }

    public function setActive(Actor $admin, int $userId, bool $active): User
    {
        $admin->mustBe(Role::Administrador);
        $user = $this->get($userId);
        $active ? $user->activate() : $user->deactivate($admin->id);
        $this->users->save($user);
        $this->audit->record($admin->id, $active ? 'usuario.activado' : 'usuario.desactivado', 'usuario', $userId);

        return $user;
    }

    public function resetPassword(Actor $admin, int $userId, string $newPassword): void
    {
        $admin->mustBe(Role::Administrador);
        $user = $this->get($userId);
        PasswordPolicy::assertValid($newPassword);
        $user->changePasswordHash($this->hasher->hash($newPassword));
        $this->users->save($user);
        $this->audit->record($admin->id, 'usuario.password_restablecida', 'usuario', $userId);
    }

    private function get(int $id): User
    {
        return $this->users->findById($id) ?? throw NotFound::entity('usuario', $id);
    }

    private function assertUnique(InstitutionalEmail $email, string $institutionalId, ?int $except = null): void
    {
        if ($this->users->emailTaken($email, $except)) {
            throw Conflict::because('email_taken', 'Ya existe una cuenta con ese correo institucional.');
        }
        if ($this->users->institutionalIdTaken($institutionalId, $except)) {
            throw Conflict::because('institutional_id_taken', 'Ya existe una cuenta con ese ID institucional.');
        }
    }
}
