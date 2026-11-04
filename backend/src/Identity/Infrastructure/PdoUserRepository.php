<?php

declare(strict_types=1);

namespace Tutorias\Identity\Infrastructure;

use Tutorias\Identity\Domain\InstitutionalEmail;
use Tutorias\Identity\Domain\User;
use Tutorias\Identity\Domain\UserRepository;
use Tutorias\Identity\Domain\UserStatus;
use Tutorias\Shared\Domain\Role;

final class PdoUserRepository implements UserRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByEmail(InstitutionalEmail $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
        $stmt->execute([$email->value]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function emailTaken(InstitutionalEmail $email, ?int $exceptUserId = null): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE email = ? AND id <> ?');
        $stmt->execute([$email->value, $exceptUserId ?? 0]);

        return (bool) $stmt->fetchColumn();
    }

    public function institutionalIdTaken(string $institutionalId, ?int $exceptUserId = null): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE id_institucional = ? AND id <> ?');
        $stmt->execute([$institutionalId, $exceptUserId ?? 0]);

        return (bool) $stmt->fetchColumn();
    }

    public function save(User $user): void
    {
        $params = [
            'id_institucional' => $user->institutionalId(),
            'nombre' => $user->name(),
            'email' => $user->email()->value,
            'password_hash' => $user->passwordHash(),
            'rol' => $user->role()->value,
            'programa' => $user->program(),
            'estado' => $user->status()->value,
            'consentimiento' => $user->dataConsentAt()?->format('Y-m-d H:i:s'),
            'ultimo_acceso' => $user->lastLoginAt()?->format('Y-m-d H:i:s'),
        ];

        if (!$user->hasId()) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO usuarios (id_institucional, nombre, email, password_hash, rol, programa_academico, estado, consentimiento_datos_en, ultimo_acceso_en, creado_en)
                 VALUES (:id_institucional, :nombre, :email, :password_hash, :rol, :programa, :estado, :consentimiento, :ultimo_acceso, :creado)'
            );
            $stmt->execute($params + ['creado' => $user->createdAt()->format('Y-m-d H:i:s')]);
            $user->assignId((int) $this->pdo->lastInsertId());

            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE usuarios SET id_institucional = :id_institucional, nombre = :nombre, email = :email, password_hash = :password_hash,
                rol = :rol, programa_academico = :programa, estado = :estado, consentimiento_datos_en = :consentimiento, ultimo_acceso_en = :ultimo_acceso
             WHERE id = :id'
        );
        $stmt->execute($params + ['id' => $user->id()]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): User
    {
        $tz = new \DateTimeZone('America/Bogota');

        return User::fromPersistence(
            (int) $row['id'],
            (string) $row['id_institucional'],
            (string) $row['nombre'],
            (string) $row['email'],
            (string) $row['password_hash'],
            Role::from((string) $row['rol']),
            $row['programa_academico'] !== null ? (string) $row['programa_academico'] : null,
            UserStatus::from((string) $row['estado']),
            $row['consentimiento_datos_en'] ? new \DateTimeImmutable((string) $row['consentimiento_datos_en'], $tz) : null,
            $row['ultimo_acceso_en'] ? new \DateTimeImmutable((string) $row['ultimo_acceso_en'], $tz) : null,
            new \DateTimeImmutable((string) $row['creado_en'], $tz),
        );
    }
}
