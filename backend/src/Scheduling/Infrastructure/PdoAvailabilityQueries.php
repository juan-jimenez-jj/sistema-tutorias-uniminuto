<?php

declare(strict_types=1);

namespace Tutorias\Scheduling\Infrastructure;

use Tutorias\Scheduling\Application\AvailabilityQueries;
use Tutorias\Scheduling\Domain\Weekday;

final class PdoAvailabilityQueries implements AvailabilityQueries
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function slotsOfTeacher(int $teacherId, \DateTimeImmutable $now): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT f.*, m.nombre AS materia_nombre, m.codigo AS materia_codigo,
                    (SELECT COUNT(*) FROM tutorias t WHERE t.franja_id = f.id AND t.estado = 'programada' AND t.fecha >= :hoy) AS reservas_futuras
             FROM franjas_disponibilidad f JOIN materias m ON m.id = f.materia_id
             WHERE f.docente_id = :docente
             ORDER BY f.activa DESC, f.dia_semana, f.hora_inicio"
        );
        $stmt->execute(['docente' => $teacherId, 'hoy' => $now->format('Y-m-d')]);

        return array_map(static fn (array $r) => [
            'id' => (int) $r['id'],
            'materiaId' => (int) $r['materia_id'],
            'materiaNombre' => $r['materia_nombre'],
            'materiaCodigo' => $r['materia_codigo'],
            'diaSemana' => (int) $r['dia_semana'],
            'diaNombre' => Weekday::from((int) $r['dia_semana'])->label(),
            'horaInicio' => substr((string) $r['hora_inicio'], 0, 5),
            'horaFin' => substr((string) $r['hora_fin'], 0, 5),
            'lugar' => $r['lugar'],
            'modalidad' => $r['modalidad'],
            'cupo' => (int) $r['cupo'],
            'activa' => (bool) $r['activa'],
            'reservasFuturas' => (int) $r['reservas_futuras'],
        ], $stmt->fetchAll());
    }

    public function subjectsOfTeacher(int $teacherId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT m.id, m.codigo, m.nombre, m.area FROM docente_materia dm JOIN materias m ON m.id = dm.materia_id
             WHERE dm.docente_id = ? AND m.estado = 'activa' ORDER BY m.nombre"
        );
        $stmt->execute([$teacherId]);

        return array_map(static fn (array $r) => ['id' => (int) $r['id'], 'codigo' => $r['codigo'], 'nombre' => $r['nombre'], 'area' => $r['area']], $stmt->fetchAll());
    }
}
