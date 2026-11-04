<?php

declare(strict_types=1);

namespace Tutorias\Catalog\Infrastructure;

use Tutorias\Catalog\Application\CatalogQueries;

final class PdoCatalogQueries implements CatalogQueries
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function subjects(bool $includeInactive): array
    {
        $where = $includeInactive ? '' : "WHERE m.estado = 'activa'";
        $rows = $this->pdo->query(
            "SELECT m.id, m.codigo, m.nombre, m.descripcion, m.area, m.estado,
                    COUNT(DISTINCT CASE WHEN u.estado = 'activo' THEN dm.docente_id END) AS docentes,
                    COUNT(DISTINCT CASE WHEN f.activa = 1 AND u.estado = 'activo' THEN f.id END) AS franjas
             FROM materias m
             LEFT JOIN docente_materia dm ON dm.materia_id = m.id
             LEFT JOIN usuarios u ON u.id = dm.docente_id
             LEFT JOIN franjas_disponibilidad f ON f.materia_id = m.id AND f.docente_id = dm.docente_id
             {$where}
             GROUP BY m.id ORDER BY m.area, m.nombre"
        )->fetchAll();

        return array_map(static fn (array $r) => [
            'id' => (int) $r['id'],
            'codigo' => $r['codigo'],
            'nombre' => $r['nombre'],
            'descripcion' => $r['descripcion'],
            'area' => $r['area'],
            'estado' => $r['estado'],
            'docentes' => (int) $r['docentes'],
            'franjasActivas' => (int) $r['franjas'],
        ], $rows);
    }

    public function teachersForSubject(int $subjectId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.id, u.nombre, u.programa_academico AS area,
                    COUNT(DISTINCT f.id) AS franjas,
                    (SELECT ROUND(AVG(t.calificacion), 1) FROM tutorias t WHERE t.docente_id = u.id AND t.calificacion IS NOT NULL) AS calificacion,
                    (SELECT COUNT(*) FROM tutorias t WHERE t.docente_id = u.id AND t.estado = 'completada') AS completadas
             FROM docente_materia dm
             JOIN usuarios u ON u.id = dm.docente_id AND u.estado = 'activo'
             LEFT JOIN franjas_disponibilidad f ON f.docente_id = u.id AND f.materia_id = dm.materia_id AND f.activa = 1
             WHERE dm.materia_id = ?
             GROUP BY u.id ORDER BY franjas DESC, u.nombre"
        );
        $stmt->execute([$subjectId]);

        return array_map(static fn (array $r) => [
            'id' => (int) $r['id'],
            'nombre' => $r['nombre'],
            'area' => $r['area'],
            'franjasActivas' => (int) $r['franjas'],
            'calificacionPromedio' => $r['calificacion'] !== null ? (float) $r['calificacion'] : null,
            'tutoriasCompletadas' => (int) $r['completadas'],
        ], $stmt->fetchAll());
    }
}
