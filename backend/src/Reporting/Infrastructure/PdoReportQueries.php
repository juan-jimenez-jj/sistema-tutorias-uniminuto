<?php

declare(strict_types=1);

namespace Tutorias\Reporting\Infrastructure;

use Tutorias\Reporting\Application\ReportQueries;

/** Consultas agregadas para la coordinación académica (apoyan los índices ix_tutorias_reportes). */
final class PdoReportQueries implements ReportQueries
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function dashboard(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $p = ['desde' => $from->format('Y-m-d'), 'hasta' => $to->format('Y-m-d')];

        $totals = $this->one(
            "SELECT COUNT(*) AS total,
                    SUM(estado = 'programada') AS programadas,
                    SUM(estado = 'completada') AS completadas,
                    SUM(estado = 'cancelada') AS canceladas,
                    SUM(estado = 'no_asistio') AS inasistencias,
                    COUNT(DISTINCT estudiante_id) AS estudiantes,
                    COUNT(DISTINCT docente_id) AS docentes,
                    ROUND(AVG(calificacion), 2) AS calificacion,
                    COUNT(calificacion) AS calificadas,
                    COALESCE(SUM(CASE WHEN estado = 'completada' THEN TIME_TO_SEC(TIMEDIFF(hora_fin, hora_inicio)) END), 0) / 3600 AS horas
             FROM tutorias WHERE fecha BETWEEN :desde AND :hasta",
            $p
        );
        $closed = (int) $totals['completadas'] + (int) $totals['inasistencias'];

        return [
            'totales' => [
                'tutorias' => (int) $totals['total'],
                'programadas' => (int) $totals['programadas'],
                'completadas' => (int) $totals['completadas'],
                'canceladas' => (int) $totals['canceladas'],
                'inasistencias' => (int) $totals['inasistencias'],
                'estudiantesAtendidos' => (int) $totals['estudiantes'],
                'docentesActivos' => (int) $totals['docentes'],
                'calificacionPromedio' => $totals['calificacion'] !== null ? (float) $totals['calificacion'] : null,
                'tutoriasCalificadas' => (int) $totals['calificadas'],
                'horasAcompanamiento' => round((float) $totals['horas'], 1),
                'tasaCancelacion' => (int) $totals['total'] > 0 ? round((int) $totals['canceladas'] / (int) $totals['total'] * 100, 1) : 0.0,
                'tasaAsistencia' => $closed > 0 ? round((int) $totals['completadas'] / $closed * 100, 1) : null,
            ],
            'porMes' => array_map(static fn (array $r) => [
                'mes' => $r['mes'],
                'completadas' => (int) $r['completadas'],
                'canceladas' => (int) $r['canceladas'],
                'programadas' => (int) $r['programadas'],
                'inasistencias' => (int) $r['inasistencias'],
            ], $this->all(
                "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes,
                        SUM(estado = 'completada') AS completadas, SUM(estado = 'cancelada') AS canceladas,
                        SUM(estado = 'programada') AS programadas, SUM(estado = 'no_asistio') AS inasistencias
                 FROM tutorias WHERE fecha BETWEEN :desde AND :hasta GROUP BY mes ORDER BY mes",
                $p
            )),
            'porMateria' => array_map(static fn (array $r) => [
                'materiaId' => (int) $r['id'],
                'materia' => $r['nombre'],
                'area' => $r['area'],
                'total' => (int) $r['total'],
                'completadas' => (int) $r['completadas'],
                'canceladas' => (int) $r['canceladas'],
                'calificacion' => $r['calificacion'] !== null ? (float) $r['calificacion'] : null,
            ], $this->all(
                "SELECT m.id, m.nombre, m.area, COUNT(*) AS total, SUM(t.estado = 'completada') AS completadas,
                        SUM(t.estado = 'cancelada') AS canceladas, ROUND(AVG(t.calificacion), 2) AS calificacion
                 FROM tutorias t JOIN materias m ON m.id = t.materia_id
                 WHERE t.fecha BETWEEN :desde AND :hasta GROUP BY m.id ORDER BY total DESC",
                $p
            )),
            'porDocente' => array_map(static fn (array $r) => [
                'docenteId' => (int) $r['id'],
                'docente' => $r['nombre'],
                'total' => (int) $r['total'],
                'completadas' => (int) $r['completadas'],
                'inasistencias' => (int) $r['inasistencias'],
                'canceladasPorDocente' => (int) $r['canc_doc'],
                'estudiantes' => (int) $r['estudiantes'],
                'calificacion' => $r['calificacion'] !== null ? (float) $r['calificacion'] : null,
            ], $this->all(
                "SELECT u.id, u.nombre, COUNT(*) AS total, SUM(t.estado = 'completada') AS completadas,
                        SUM(t.estado = 'no_asistio') AS inasistencias, SUM(t.cancelada_por = 'docente') AS canc_doc,
                        COUNT(DISTINCT t.estudiante_id) AS estudiantes, ROUND(AVG(t.calificacion), 2) AS calificacion
                 FROM tutorias t JOIN usuarios u ON u.id = t.docente_id
                 WHERE t.fecha BETWEEN :desde AND :hasta GROUP BY u.id ORDER BY total DESC",
                $p
            )),
            'porPrograma' => array_map(static fn (array $r) => [
                'programa' => $r['programa'] ?? 'Sin programa',
                'estudiantes' => (int) $r['estudiantes'],
                'tutorias' => (int) $r['total'],
            ], $this->all(
                "SELECT e.programa_academico AS programa, COUNT(DISTINCT t.estudiante_id) AS estudiantes, COUNT(*) AS total
                 FROM tutorias t JOIN usuarios e ON e.id = t.estudiante_id
                 WHERE t.fecha BETWEEN :desde AND :hasta GROUP BY e.programa_academico ORDER BY total DESC",
                $p
            )),
            'mapaCalor' => array_map(static fn (array $r) => [
                'diaSemana' => (int) $r['dia'],
                'hora' => (int) $r['hora'],
                'tutorias' => (int) $r['total'],
            ], $this->all(
                "SELECT WEEKDAY(fecha) + 1 AS dia, HOUR(hora_inicio) AS hora, COUNT(*) AS total
                 FROM tutorias WHERE fecha BETWEEN :desde AND :hasta AND estado <> 'cancelada'
                 GROUP BY dia, hora",
                $p
            )),
            'motivosCancelacion' => array_map(static fn (array $r) => [
                'canceladaPor' => $r['cancelada_por'],
                'total' => (int) $r['total'],
            ], $this->all(
                "SELECT cancelada_por, COUNT(*) AS total FROM tutorias
                 WHERE fecha BETWEEN :desde AND :hasta AND estado = 'cancelada' GROUP BY cancelada_por",
                $p
            )),
        ];
    }

    public function exportRows(\DateTimeImmutable $from, \DateTimeImmutable $to): iterable
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.codigo, t.fecha, t.hora_inicio, t.hora_fin, m.codigo AS materia_codigo, m.nombre AS materia,
                    d.nombre AS docente, e.id_institucional AS estudiante_id, e.nombre AS estudiante, e.programa_academico AS programa,
                    t.modalidad, t.lugar, t.estado, t.cancelada_por, t.calificacion, t.tema
             FROM tutorias t
             JOIN materias m ON m.id = t.materia_id
             JOIN usuarios d ON d.id = t.docente_id
             JOIN usuarios e ON e.id = t.estudiante_id
             WHERE t.fecha BETWEEN ? AND ? ORDER BY t.fecha, t.hora_inicio"
        );
        $stmt->execute([$from->format('Y-m-d'), $to->format('Y-m-d')]);
        while ($row = $stmt->fetch()) {
            yield $row;
        }
    }

    public function auditTrail(?string $action, int $page, int $pageSize): array
    {
        $where = '1 = 1';
        $params = [];
        if ($action !== null && $action !== '') {
            $where = 'a.accion LIKE :accion';
            $params['accion'] = addcslashes($action, '%_\\') . '%';
        }
        $pageSize = max(1, min(100, $pageSize));
        $page = max(1, $page);
        $offset = ($page - 1) * $pageSize;

        $count = $this->pdo->prepare("SELECT COUNT(*) FROM auditoria a WHERE {$where}");
        $count->execute($params);
        $rows = $this->all(
            "SELECT a.id, a.accion, a.entidad, a.entidad_id, a.detalle, a.ip, a.creado_en, u.nombre AS usuario, u.rol
             FROM auditoria a LEFT JOIN usuarios u ON u.id = a.usuario_id
             WHERE {$where} ORDER BY a.id DESC LIMIT {$pageSize} OFFSET {$offset}",
            $params
        );

        return [
            'items' => array_map(static fn (array $r) => [
                'id' => (int) $r['id'],
                'accion' => $r['accion'],
                'entidad' => $r['entidad'],
                'entidadId' => $r['entidad_id'],
                'detalle' => $r['detalle'] ? json_decode((string) $r['detalle'], true) : null,
                'ip' => $r['ip'],
                'usuario' => $r['usuario'],
                'rol' => $r['rol'],
                'fecha' => $r['creado_en'],
            ], $rows),
            'total' => (int) $count->fetchColumn(),
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }

    /** @param array<string, mixed> $params @return array<string, mixed> */
    private function one(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (array) $stmt->fetch();
    }

    /** @param array<string, mixed> $params @return list<array<string, mixed>> */
    private function all(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
