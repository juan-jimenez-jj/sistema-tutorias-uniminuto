<?php

declare(strict_types=1);

namespace Tutorias\Scheduling\Infrastructure;

use Tutorias\Scheduling\Domain\AvailabilitySlot;
use Tutorias\Scheduling\Domain\Modality;
use Tutorias\Scheduling\Domain\SlotRepository;
use Tutorias\Scheduling\Domain\TimeRange;
use Tutorias\Scheduling\Domain\Weekday;

final class PdoSlotRepository implements SlotRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function findById(int $id): ?AvailabilitySlot
    {
        $stmt = $this->pdo->prepare('SELECT * FROM franjas_disponibilidad WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function activeSlotsOfTeacherOn(int $teacherId, Weekday $weekday): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM franjas_disponibilidad WHERE docente_id = ? AND dia_semana = ? AND activa = 1 FOR UPDATE');
        $stmt->execute([$teacherId, $weekday->value]);

        return array_map(fn (array $r) => $this->hydrate($r), $stmt->fetchAll());
    }

    public function save(AvailabilitySlot $slot): void
    {
        $params = [
            'materia' => $slot->subjectId(),
            'dia' => $slot->weekday()->value,
            'inicio' => $slot->time()->start(),
            'fin' => $slot->time()->end(),
            'lugar' => $slot->location(),
            'modalidad' => $slot->modality()->value,
            'cupo' => $slot->capacity(),
            'activa' => $slot->isActive() ? 1 : 0,
        ];
        if ($slot->id() === null) {
            $this->pdo->prepare(
                'INSERT INTO franjas_disponibilidad (docente_id, materia_id, dia_semana, hora_inicio, hora_fin, lugar, modalidad, cupo, activa)
                 VALUES (:docente, :materia, :dia, :inicio, :fin, :lugar, :modalidad, :cupo, :activa)'
            )->execute($params + ['docente' => $slot->teacherId()]);
            $slot->assignId((int) $this->pdo->lastInsertId());

            return;
        }
        $this->pdo->prepare(
            'UPDATE franjas_disponibilidad SET materia_id = :materia, dia_semana = :dia, hora_inicio = :inicio, hora_fin = :fin,
                lugar = :lugar, modalidad = :modalidad, cupo = :cupo, activa = :activa WHERE id = :id'
        )->execute($params + ['id' => $slot->id()]);
    }

    /** @param array<string, mixed> $r */
    private function hydrate(array $r): AvailabilitySlot
    {
        return AvailabilitySlot::fromPersistence(
            (int) $r['id'],
            (int) $r['docente_id'],
            (int) $r['materia_id'],
            Weekday::from((int) $r['dia_semana']),
            TimeRange::fromStrings((string) $r['hora_inicio'], (string) $r['hora_fin']),
            (string) $r['lugar'],
            Modality::from((string) $r['modalidad']),
            (int) $r['cupo'],
            (bool) $r['activa'],
        );
    }
}
