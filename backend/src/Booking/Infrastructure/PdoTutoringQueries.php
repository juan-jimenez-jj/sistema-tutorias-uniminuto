<?php

declare(strict_types=1);

namespace Tutorias\Booking\Infrastructure;

use Tutorias\Booking\Application\TutoringQueries;

final class PdoTutoringQueries implements TutoringQueries
{
    private const SELECT = "SELECT t.*, m.nombre AS materia_nombre, m.codigo AS materia_codigo, m.area AS materia_area,
            e.nombre AS estudiante_nombre, e.id_institucional AS estudiante_id_institucional, e.email AS estudiante_email, e.programa_academico AS estudiante_programa,
            d.nombre AS docente_nombre, d.email AS docente_email
        FROM tutorias t
        JOIN materias m ON m.id = t.materia_id
        JOIN usuarios e ON e.id = t.estudiante_id
        JOIN usuarios d ON d.id = t.docente_id";

    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function search(?int $studentId, ?int $teacherId, array $filters, \DateTimeImmutable $now, int $page, int $pageSize): array
    {
        $where = ['1 = 1'];
        $params = [];
        if ($studentId !== null) {
            $where[] = 't.estudiante_id = :estudiante';
            $params['estudiante'] = $studentId;
        }
        if ($teacherId !== null) {
            $where[] = 't.docente_id = :docente';
            $params['docente'] = $teacherId;
        }
        if (in_array($filters['estado'] ?? null, ['programada', 'completada', 'cancelada', 'no_asistio'], true)) {
            $where[] = 't.estado = :estado';
            $params['estado'] = $filters['estado'];
        }
        $nowSql = $now->format('Y-m-d H:i:s');
        $order = 't.fecha DESC, t.hora_inicio DESC';
        if (($filters['alcance'] ?? null) === 'proximas') {
            $where[] = "t.estado = 'programada' AND TIMESTAMP(t.fecha, t.hora_fin) >= :ahora";
            $params['ahora'] = $nowSql;
            $order = 't.fecha ASC, t.hora_inicio ASC';
        } elseif (($filters['alcance'] ?? null) === 'pendientes_cierre') {
            $where[] = "t.estado = 'programada' AND TIMESTAMP(t.fecha, t.hora_inicio) <= :ahora";
            $params['ahora'] = $nowSql;
        } elseif (($filters['alcance'] ?? null) === 'historial') {
            $where[] = "(t.estado <> 'programada' OR TIMESTAMP(t.fecha, t.hora_fin) < :ahora)";
            $params['ahora'] = $nowSql;
        }
        if (!empty($filters['materiaId'])) {
            $where[] = 't.materia_id = :materia';
            $params['materia'] = (int) $filters['materiaId'];
        }
        if (!empty($filters['desde'])) {
            $where[] = 't.fecha >= :desde';
            $params['desde'] = $filters['desde'];
        }
        if (!empty($filters['hasta'])) {
            $where[] = 't.fecha <= :hasta';
            $params['hasta'] = $filters['hasta'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(t.codigo LIKE :q1 OR t.tema LIKE :q2 OR e.nombre LIKE :q3 OR d.nombre LIKE :q4)';
            $like = '%' . addcslashes((string) $filters['q'], '%_\\') . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }
        $whereSql = implode(' AND ', $where);

        $count = $this->pdo->prepare("SELECT COUNT(*) FROM tutorias t JOIN usuarios e ON e.id = t.estudiante_id JOIN usuarios d ON d.id = t.docente_id WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $pageSize = max(1, min(100, $pageSize));
        $page = max(1, $page);
        $offset = ($page - 1) * $pageSize;
        $stmt = $this->pdo->prepare(self::SELECT . " WHERE {$whereSql} ORDER BY {$order} LIMIT {$pageSize} OFFSET {$offset}");
        $stmt->execute($params);

        return [
            'items' => array_map(fn (array $r) => $this->map($r, $now), $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }

    public function detail(int $id): ?array
    {
        $stmt = $this->pdo->prepare(self::SELECT . ' WHERE t.id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->map($row, new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota'))) : null;
    }

    public function studentStats(int $studentId, \DateTimeImmutable $now): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total,
                SUM(estado = 'completada') AS completadas,
                SUM(estado = 'programada' AND TIMESTAMP(fecha, hora_fin) >= :ahora) AS proximas,
                SUM(estado = 'cancelada') AS canceladas,
                SUM(estado = 'no_asistio') AS inasistencias,
                SUM(estado = 'completada' AND calificacion IS NULL) AS por_calificar,
                ROUND(AVG(calificacion), 2) AS calificacion_promedio,
                COALESCE(SUM(CASE WHEN estado = 'completada' THEN TIME_TO_SEC(TIMEDIFF(hora_fin, hora_inicio)) END), 0) / 3600 AS horas
             FROM tutorias WHERE estudiante_id = :id"
        );
        $stmt->execute(['id' => $studentId, 'ahora' => $now->format('Y-m-d H:i:s')]);
        $r = $stmt->fetch();

        $bySubject = $this->pdo->prepare(
            "SELECT m.nombre, COUNT(*) AS total, SUM(t.estado = 'completada') AS completadas
             FROM tutorias t JOIN materias m ON m.id = t.materia_id WHERE t.estudiante_id = ? GROUP BY m.id ORDER BY total DESC LIMIT 6"
        );
        $bySubject->execute([$studentId]);

        return [
            'total' => (int) $r['total'],
            'completadas' => (int) $r['completadas'],
            'proximas' => (int) $r['proximas'],
            'canceladas' => (int) $r['canceladas'],
            'inasistencias' => (int) $r['inasistencias'],
            'porCalificar' => (int) $r['por_calificar'],
            'calificacionPromedio' => $r['calificacion_promedio'] !== null ? (float) $r['calificacion_promedio'] : null,
            'horasAcompanamiento' => round((float) $r['horas'], 1),
            'porMateria' => array_map(static fn (array $x) => [
                'materia' => $x['nombre'],
                'total' => (int) $x['total'],
                'completadas' => (int) $x['completadas'],
            ], $bySubject->fetchAll()),
        ];
    }

    public function teacherStats(int $teacherId, \DateTimeImmutable $now): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                SUM(estado = 'programada' AND fecha = :hoy) AS hoy,
                SUM(estado = 'programada' AND TIMESTAMP(fecha, hora_fin) >= :ahora1 AND fecha <= :semana) AS semana,
                SUM(estado = 'programada' AND TIMESTAMP(fecha, hora_inicio) <= :ahora2) AS pendientes_cierre,
                SUM(estado = 'completada') AS completadas,
                SUM(estado = 'no_asistio') AS inasistencias,
                COUNT(DISTINCT CASE WHEN estado = 'completada' THEN estudiante_id END) AS estudiantes,
                ROUND(AVG(calificacion), 2) AS calificacion_promedio,
                COUNT(calificacion) AS calificaciones
             FROM tutorias WHERE docente_id = :id"
        );
        $stmt->execute([
            'id' => $teacherId,
            'hoy' => $now->format('Y-m-d'),
            'ahora1' => $now->format('Y-m-d H:i:s'),
            'ahora2' => $now->format('Y-m-d H:i:s'),
            'semana' => $now->modify('+7 days')->format('Y-m-d'),
        ]);
        $r = $stmt->fetch();

        $slots = $this->pdo->prepare('SELECT COUNT(*) FROM franjas_disponibilidad WHERE docente_id = ? AND activa = 1');
        $slots->execute([$teacherId]);

        return [
            'hoy' => (int) $r['hoy'],
            'proximos7Dias' => (int) $r['semana'],
            'pendientesCierre' => (int) $r['pendientes_cierre'],
            'completadas' => (int) $r['completadas'],
            'inasistencias' => (int) $r['inasistencias'],
            'estudiantesAtendidos' => (int) $r['estudiantes'],
            'calificacionPromedio' => $r['calificacion_promedio'] !== null ? (float) $r['calificacion_promedio'] : null,
            'calificaciones' => (int) $r['calificaciones'],
            'franjasActivas' => (int) $slots->fetchColumn(),
        ];
    }

    /** @param array<string, mixed> $r @return array<string, mixed> */
    private function map(array $r, \DateTimeImmutable $now): array
    {
        $startsAt = new \DateTimeImmutable($r['fecha'] . ' ' . $r['hora_inicio'], $now->getTimezone());

        return [
            'id' => (int) $r['id'],
            'codigo' => $r['codigo'],
            'estado' => $r['estado'],
            'fecha' => $r['fecha'],
            'horaInicio' => substr((string) $r['hora_inicio'], 0, 5),
            'horaFin' => substr((string) $r['hora_fin'], 0, 5),
            'iniciaEn' => $startsAt->format(DATE_ATOM),
            'yaInicio' => $startsAt <= $now,
            'lugar' => $r['lugar'],
            'modalidad' => $r['modalidad'],
            'tema' => $r['tema'],
            'materia' => ['id' => (int) $r['materia_id'], 'nombre' => $r['materia_nombre'], 'codigo' => $r['materia_codigo'], 'area' => $r['materia_area']],
            'docente' => ['id' => (int) $r['docente_id'], 'nombre' => $r['docente_nombre'], 'email' => $r['docente_email']],
            'estudiante' => [
                'id' => (int) $r['estudiante_id'],
                'nombre' => $r['estudiante_nombre'],
                'idInstitucional' => $r['estudiante_id_institucional'],
                'email' => $r['estudiante_email'],
                'programa' => $r['estudiante_programa'],
            ],
            'canceladaPor' => $r['cancelada_por'],
            'motivoCancelacion' => $r['motivo_cancelacion'],
            'canceladaEn' => $r['cancelada_en'],
            'observacionesDocente' => $r['observaciones_docente'],
            'completadaEn' => $r['completada_en'],
            'calificacion' => $r['calificacion'] !== null ? (int) $r['calificacion'] : null,
            'comentarioEstudiante' => $r['comentario_estudiante'],
            'creadoEn' => $r['creado_en'],
        ];
    }
}
