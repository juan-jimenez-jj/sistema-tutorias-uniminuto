<?php

declare(strict_types=1);

namespace Tutorias\Booking\Infrastructure;

use Tutorias\Booking\Application\OfferQueries;

final class PdoOfferQueries implements OfferQueries
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function bookableSlots(int $subjectId, ?int $teacherId): array
    {
        $sql = "SELECT f.id, f.docente_id, u.nombre AS docente_nombre, f.dia_semana, f.hora_inicio, f.hora_fin, f.lugar, f.modalidad, f.cupo
                FROM franjas_disponibilidad f
                JOIN usuarios u ON u.id = f.docente_id AND u.estado = 'activo'
                JOIN materias m ON m.id = f.materia_id AND m.estado = 'activa'
                JOIN docente_materia dm ON dm.docente_id = f.docente_id AND dm.materia_id = f.materia_id
                WHERE f.activa = 1 AND f.materia_id = :materia";
        $params = ['materia' => $subjectId];
        if ($teacherId !== null) {
            $sql .= ' AND f.docente_id = :docente';
            $params['docente'] = $teacherId;
        }
        $stmt = $this->pdo->prepare($sql . ' ORDER BY f.dia_semana, f.hora_inicio');
        $stmt->execute($params);

        return array_map(static fn (array $r) => [
            'id' => (int) $r['id'],
            'docenteId' => (int) $r['docente_id'],
            'docenteNombre' => (string) $r['docente_nombre'],
            'diaSemana' => (int) $r['dia_semana'],
            'horaInicio' => substr((string) $r['hora_inicio'], 0, 5),
            'horaFin' => substr((string) $r['hora_fin'], 0, 5),
            'lugar' => (string) $r['lugar'],
            'modalidad' => (string) $r['modalidad'],
            'cupo' => (int) $r['cupo'],
        ], $stmt->fetchAll());
    }

    public function takenSeats(array $slotIds, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        if ($slotIds === []) {
            return [];
        }
        $in = implode(',', array_map('intval', $slotIds));
        $stmt = $this->pdo->prepare(
            "SELECT franja_id, fecha, COUNT(*) AS ocupados FROM tutorias
             WHERE franja_id IN ({$in}) AND estado = 'programada' AND fecha BETWEEN ? AND ?
             GROUP BY franja_id, fecha"
        );
        $stmt->execute([$from->format('Y-m-d'), $to->format('Y-m-d')]);
        $result = [];
        foreach ($stmt->fetchAll() as $r) {
            $result[$r['franja_id'] . '|' . $r['fecha']] = (int) $r['ocupados'];
        }

        return $result;
    }

    public function studentBookings(int $studentId, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $stmt = $this->pdo->prepare("SELECT franja_id, fecha FROM tutorias WHERE estudiante_id = ? AND estado = 'programada' AND fecha BETWEEN ? AND ?");
        $stmt->execute([$studentId, $from->format('Y-m-d'), $to->format('Y-m-d')]);
        $result = [];
        foreach ($stmt->fetchAll() as $r) {
            $result[$r['franja_id'] . '|' . $r['fecha']] = true;
        }

        return $result;
    }
}
