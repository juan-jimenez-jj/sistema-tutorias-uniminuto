<?php

declare(strict_types=1);

namespace Tutorias\Booking\Infrastructure;

use Tutorias\Booking\Domain\BookableSlot;
use Tutorias\Booking\Domain\Tutoring;
use Tutorias\Booking\Domain\TutoringRepository;
use Tutorias\Shared\Domain\Conflict;

final class PdoTutoringRepository implements TutoringRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function lockBookableSlot(int $slotId): ?BookableSlot
    {
        // FOR UPDATE bloquea la fila de la franja hasta el COMMIT: las reservas concurrentes
        // sobre la misma franja quedan en cola y ven el conteo de cupos ya actualizado.
        $stmt = $this->pdo->prepare(
            "SELECT f.*, u.nombre AS docente_nombre, u.estado AS docente_estado, m.nombre AS materia_nombre, m.estado AS materia_estado,
                    EXISTS(SELECT 1 FROM docente_materia dm WHERE dm.docente_id = f.docente_id AND dm.materia_id = f.materia_id) AS dicta
             FROM franjas_disponibilidad f
             JOIN usuarios u ON u.id = f.docente_id
             JOIN materias m ON m.id = f.materia_id
             WHERE f.id = ?
             FOR UPDATE"
        );
        $stmt->execute([$slotId]);
        $r = $stmt->fetch();
        if (!$r) {
            return null;
        }

        return new BookableSlot(
            (int) $r['id'],
            (int) $r['docente_id'],
            (string) $r['docente_nombre'],
            (int) $r['materia_id'],
            (string) $r['materia_nombre'],
            (int) $r['dia_semana'],
            substr((string) $r['hora_inicio'], 0, 5),
            substr((string) $r['hora_fin'], 0, 5),
            (string) $r['lugar'],
            (string) $r['modalidad'],
            (int) $r['cupo'],
            (bool) $r['activa'],
            $r['docente_estado'] === 'activo',
            $r['materia_estado'] === 'activa',
            (bool) $r['dicta'],
        );
    }

    public function countScheduledSeats(int $slotId, \DateTimeImmutable $date): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tutorias WHERE franja_id = ? AND fecha = ? AND estado = 'programada'");
        $stmt->execute([$slotId, $date->format('Y-m-d')]);

        return (int) $stmt->fetchColumn();
    }

    public function studentHasOverlap(int $studentId, \DateTimeImmutable $date, string $start, string $end): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM tutorias WHERE estudiante_id = ? AND fecha = ? AND estado = 'programada'
             AND hora_inicio < ? AND hora_fin > ? LIMIT 1"
        );
        $stmt->execute([$studentId, $date->format('Y-m-d'), $end, $start]);

        return (bool) $stmt->fetchColumn();
    }

    public function countActiveForStudent(int $studentId, \DateTimeImmutable $now): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM tutorias WHERE estudiante_id = ? AND estado = 'programada' AND TIMESTAMP(fecha, hora_fin) > ?"
        );
        $stmt->execute([$studentId, $now->format('Y-m-d H:i:s')]);

        return (int) $stmt->fetchColumn();
    }

    public function findForUpdate(int $id): ?Tutoring
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tutorias WHERE id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Tutoring::fromPersistence($row) : null;
    }

    public function save(Tutoring $t): void
    {
        $fmt = static fn (?\DateTimeImmutable $d) => $d?->format('Y-m-d H:i:s');

        if (!$t->hasId()) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO tutorias (codigo, estudiante_id, docente_id, materia_id, franja_id, fecha, hora_inicio, hora_fin, lugar, modalidad, tema, estado)
                 VALUES (:codigo, :estudiante, :docente, :materia, :franja, :fecha, :inicio, :fin, :lugar, :modalidad, :tema, :estado)'
            );
            try {
                $stmt->execute([
                    'codigo' => $t->code(),
                    'estudiante' => $t->studentId(),
                    'docente' => $t->teacherId(),
                    'materia' => $t->subjectId(),
                    'franja' => $t->slotId(),
                    'fecha' => $t->date()->format('Y-m-d'),
                    'inicio' => $t->start(),
                    'fin' => $t->end(),
                    'lugar' => $t->location(),
                    'modalidad' => $t->modality(),
                    'tema' => $t->topic(),
                    'estado' => $t->status()->value,
                ]);
            } catch (\PDOException $e) {
                if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'uq_tutorias_reserva_unica')) {
                    throw Conflict::because('already_booked', 'Ya tienes una reserva en esa franja para la fecha elegida.');
                }
                throw $e;
            }
            $t->assignId((int) $this->pdo->lastInsertId());

            return;
        }

        // Bloqueo optimista: si otro proceso modificó la fila, version ya no coincide.
        $stmt = $this->pdo->prepare(
            'UPDATE tutorias SET estado = :estado, cancelada_por = :cancelada_por, motivo_cancelacion = :motivo, cancelada_en = :cancelada_en,
                observaciones_docente = :obs, completada_en = :completada_en, calificacion = :calificacion,
                comentario_estudiante = :comentario, calificada_en = :calificada_en, version = version + 1
             WHERE id = :id AND version = :version'
        );
        $stmt->execute([
            'estado' => $t->status()->value,
            'cancelada_por' => $t->cancelledBy()?->value,
            'motivo' => $t->cancellationReason(),
            'cancelada_en' => $fmt($t->cancelledAt()),
            'obs' => $t->teacherNotes(),
            'completada_en' => $fmt($t->completedAt()),
            'calificacion' => $t->rating(),
            'comentario' => $t->studentComment(),
            'calificada_en' => $fmt($t->ratedAt()),
            'id' => $t->id(),
            'version' => $t->version(),
        ]);
        if ($stmt->rowCount() === 0) {
            throw Conflict::because('concurrent_modification', 'La tutoría fue modificada por otra persona. Recarga e intenta de nuevo.');
        }
        $t->incrementVersion();
    }
}
