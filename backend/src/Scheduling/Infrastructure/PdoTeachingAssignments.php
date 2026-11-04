<?php

declare(strict_types=1);

namespace Tutorias\Scheduling\Infrastructure;

use Tutorias\Scheduling\Application\TeachingAssignments;

final class PdoTeachingAssignments implements TeachingAssignments
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function teaches(int $teacherId, int $subjectId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM docente_materia dm JOIN materias m ON m.id = dm.materia_id AND m.estado = 'activa'
             WHERE dm.docente_id = ? AND dm.materia_id = ?"
        );
        $stmt->execute([$teacherId, $subjectId]);

        return (bool) $stmt->fetchColumn();
    }
}
