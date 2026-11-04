<?php

declare(strict_types=1);

namespace Tutorias\Identity\Domain;

use Tutorias\Shared\Domain\BusinessRuleViolation;
use Tutorias\Shared\Domain\Role;

/** Entidad Usuario (agregado raíz del módulo de Identidad). */
final class User
{
    private function __construct(
        private ?int $id,
        private string $institutionalId,
        private string $name,
        private InstitutionalEmail $email,
        private string $passwordHash,
        private Role $role,
        private ?string $program,
        private UserStatus $status,
        private ?\DateTimeImmutable $dataConsentAt,
        private ?\DateTimeImmutable $lastLoginAt,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function registerStudent(
        string $institutionalId,
        string $name,
        InstitutionalEmail $email,
        string $passwordHash,
        string $program,
        \DateTimeImmutable $now,
    ): self {
        // El registro público solo crea estudiantes y exige la autorización de datos (Ley 1581 de 2012).
        return new self(null, strtoupper($institutionalId), $name, $email, $passwordHash, Role::Estudiante, $program, UserStatus::Activo, $now, null, $now);
    }

    public static function createByAdministrator(
        string $institutionalId,
        string $name,
        InstitutionalEmail $email,
        string $passwordHash,
        Role $role,
        ?string $program,
        \DateTimeImmutable $now,
    ): self {
        return new self(null, strtoupper($institutionalId), $name, $email, $passwordHash, $role, $program, UserStatus::Activo, null, null, $now);
    }

    /** Reconstitución desde persistencia. */
    public static function fromPersistence(
        int $id,
        string $institutionalId,
        string $name,
        string $email,
        string $passwordHash,
        Role $role,
        ?string $program,
        UserStatus $status,
        ?\DateTimeImmutable $dataConsentAt,
        ?\DateTimeImmutable $lastLoginAt,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $institutionalId, $name, InstitutionalEmail::trusted($email), $passwordHash, $role, $program, $status, $dataConsentAt, $lastLoginAt, $createdAt);
    }

    public function assignId(int $id): void
    {
        if ($this->id !== null) {
            throw new \LogicException('El usuario ya tiene identificador.');
        }
        $this->id = $id;
    }

    public function updateProfile(string $name, ?string $program): void
    {
        $this->name = $name;
        $this->program = $program;
    }

    public function changeEmail(InstitutionalEmail $email): void
    {
        $this->email = $email;
    }

    public function changeRole(Role $role): void
    {
        $this->role = $role;
    }

    public function changePasswordHash(string $hash): void
    {
        $this->passwordHash = $hash;
    }

    public function deactivate(int $byUserId): void
    {
        if ($byUserId === $this->id) {
            throw BusinessRuleViolation::rule('cannot_deactivate_self', 'No puedes desactivar tu propia cuenta.');
        }
        $this->status = UserStatus::Inactivo;
    }

    public function activate(): void
    {
        $this->status = UserStatus::Activo;
    }

    public function recordLogin(\DateTimeImmutable $at): void
    {
        $this->lastLoginAt = $at;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Activo;
    }

    public function id(): int
    {
        if ($this->id === null) {
            throw new \LogicException('Usuario aún no persistido.');
        }

        return $this->id;
    }

    public function hasId(): bool
    {
        return $this->id !== null;
    }

    public function institutionalId(): string
    { return $this->institutionalId; }
    public function name(): string
    { return $this->name; }
    public function email(): InstitutionalEmail
    { return $this->email; }
    public function passwordHash(): string
    { return $this->passwordHash; }
    public function role(): Role
    { return $this->role; }
    public function program(): ?string
    { return $this->program; }
    public function status(): UserStatus
    { return $this->status; }
    public function dataConsentAt(): ?\DateTimeImmutable
    { return $this->dataConsentAt; }
    public function lastLoginAt(): ?\DateTimeImmutable
    { return $this->lastLoginAt; }
    public function createdAt(): \DateTimeImmutable
    { return $this->createdAt; }
}
