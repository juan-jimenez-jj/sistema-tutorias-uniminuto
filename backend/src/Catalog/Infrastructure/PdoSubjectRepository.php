<?php

declare(strict_types=1);

namespace Tutorias\Catalog\Infrastructure;

use Tutorias\Catalog\Domain\Subject;
use Tutorias\Catalog\Domain\SubjectRepository;

final class PdoSubjectRepository implements SubjectRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function findById(int $id): ?Subject
    {
        $stmt = $this->pdo->prepare('SELECT * FROM materias WHERE id = ?');
        $stmt->execute([$id]);
        $r = $stmt->fetch();

        return $r ? Subject::fromPersistence((int) $r['id'], $r['codigo'], $r['nombre'], $r['descripcion'], $r['area'], $r['estado'] === 'activa') : null;
    }

    public function codeTaken(string $code, ?int $exceptId = null): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM materias WHERE codigo = ? AND id <> ?');
        $stmt->execute([$code, $exceptId ?? 0]);

        return (bool) $stmt->fetchColumn();
    }

    public function save(Subject $subject): void
    {
        $params = [
            'codigo' => $subject->code(),
            'nombre' => $subject->name(),
            'descripcion' => $subject->description(),
            'area' => $subject->area(),
            'estado' => $subject->isActive() ? 'activa' : 'inactiva',
        ];
        if ($subject->id() === null) {
            $this->pdo->prepare('INSERT INTO materias (codigo, nombre, descripcion, area, estado) VALUES (:codigo, :nombre, :descripcion, :area, :estado)')->execute($params);
            $subject->assignId((int) $this->pdo->lastInsertId());

            return;
        }
        $this->pdo->prepare('UPDATE materias SET codigo = :codigo, nombre = :nombre, descripcion = :descripcion, area = :area, estado = :estado WHERE id = :id')
            ->execute($params + ['id' => $subject->id()]);
    }

    public function replaceTeacherSubjects(int $teacherId, array $subjectIds): void
    {
        $this->pdo->prepare('DELETE FROM docente_materia WHERE docente_id = ?')->execute([$teacherId]);
        $insert = $this->pdo->prepare('INSERT INTO docente_materia (docente_id, materia_id) VALUES (?, ?)');
        foreach ($subjectIds as $subjectId) {
            $insert->execute([$teacherId, $subjectId]);
        }
        // Las franjas de materias que el docente ya no dicta se desactivan (no se borran: preservan historial).
        if ($subjectIds === []) {
            $this->pdo->prepare('UPDATE franjas_disponibilidad SET activa = 0 WHERE docente_id = ?')->execute([$teacherId]);
        } else {
            $in = implode(',', array_map('intval', $subjectIds));
            $this->pdo->prepare("UPDATE franjas_disponibilidad SET activa = 0 WHERE docente_id = ? AND materia_id NOT IN ({$in})")->execute([$teacherId]);
        }
    }

    public function teacherTeaches(int $teacherId, int $subjectId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM docente_materia WHERE docente_id = ? AND materia_id = ?');
        $stmt->execute([$teacherId, $subjectId]);

        return (bool) $stmt->fetchColumn();
    }

    public function isActiveTeacher(int $userId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM usuarios WHERE id = ? AND rol = 'docente' AND estado = 'activo'");
        $stmt->execute([$userId]);

        return (bool) $stmt->fetchColumn();
    }

    public function missingIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $in = implode(',', array_map('intval', $ids));
        $found = $this->pdo->query("SELECT id FROM materias WHERE id IN ({$in})")->fetchAll(\PDO::FETCH_COLUMN);

        return array_values(array_diff($ids, array_map('intval', $found)));
    }
}
