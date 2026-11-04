<?php

declare(strict_types=1);

namespace Tutorias\Identity\Application;

use Tutorias\Identity\Domain\PasswordHasher;
use Tutorias\Identity\Domain\PasswordPolicy;
use Tutorias\Identity\Domain\UserRepository;
use Tutorias\Shared\Application\Actor;
use Tutorias\Shared\Application\AuditLog;
use Tutorias\Shared\Domain\NotFound;
use Tutorias\Shared\Domain\ValidationError;

final class ChangeOwnPassword
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly AuditLog $audit,
    ) {
    }

    public function execute(Actor $actor, string $currentPassword, string $newPassword): void
    {
        $user = $this->users->findById($actor->id) ?? throw NotFound::entity('usuario', $actor->id);
        if (!$this->hasher->verify($currentPassword, $user->passwordHash())) {
            throw ValidationError::field('passwordActual', 'La contraseña actual no es correcta.');
        }
        PasswordPolicy::assertValid($newPassword, 'passwordNueva');
        if ($currentPassword === $newPassword) {
            throw ValidationError::field('passwordNueva', 'La nueva contraseña debe ser distinta de la actual.');
        }
        $user->changePasswordHash($this->hasher->hash($newPassword));
        $this->users->save($user);
        $this->audit->record($actor->id, 'usuario.password_cambiada', 'usuario', $actor->id);
    }
}
