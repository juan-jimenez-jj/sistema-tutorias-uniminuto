<?php

declare(strict_types=1);

namespace Tutorias\Platform\Console;

/**
 * Migración de datos del sistema heredado (tarea T40 del plan: "migrar datos iniciales con
 * validación de integridad"). Lee el volcado SQL de phpMyAdmin, valida cada tutoría contra
 * las reglas del nuevo modelo y produce un REPORTE DE CONCILIACIÓN con los registros
 * aceptados y rechazados (y el motivo). Las contraseñas bcrypt heredadas se conservan:
 * se re-hashean con el nuevo costo la próxima vez que el usuario inicie sesión.
 */
final class LegacyImporter
{
    private const DAYS = ['Lunes' => 1, 'Martes' => 2, 'Miércoles' => 3, 'Jueves' => 4, 'Viernes' => 5, 'Sábado' => 6];

    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function run(string $file, bool $dryRun, callable $out): void
    {
        if (!is_file($file)) {
            throw new \RuntimeException("No existe el archivo {$file}.");
        }
        $sql = (string) file_get_contents($file);
        $legacyUsers = $this->rows($sql, 'usuarios');
        $legacySlots = $this->rows($sql, 'disponibilidad_docentes');
        $legacyTutorings = $this->rows($sql, 'tutorias');
        $out(sprintf('Leídos del sistema heredado: %d usuarios, %d franjas, %d tutorías.', count($legacyUsers), count($legacySlots), count($legacyTutorings)));

        $report = [];
        $rejected = [];
        $this->pdo->beginTransaction();
        try {
            // 1. Estudiantes (los docentes y el administrador ya se migraron con db:seed).
            $userMap = [];
            $find = $this->pdo->prepare('SELECT id, rol FROM usuarios WHERE email = ?');
            $insertUser = $this->pdo->prepare(
                "INSERT INTO usuarios (id_institucional, nombre, email, password_hash, rol, programa_academico, estado, creado_en)
                 VALUES (?, ?, ?, ?, 'estudiante', ?, ?, ?)"
            );
            $createdStudents = 0;
            foreach ($legacyUsers as $u) {
                $email = mb_strtolower((string) $u['email']);
                $find->execute([$email]);
                $existing = $find->fetch();
                if ($existing) {
                    $userMap[(int) $u['id']] = ['id' => (int) $existing['id'], 'rol' => $existing['rol']];
                    continue;
                }
                if ($u['rol'] !== 'estudiante') {
                    continue;
                }
                if (!$dryRun) {
                    $insertUser->execute([
                        'L-' . $u['id_institucional'], $u['nombre'], $email, $u['password'], $u['programa_academico'],
                        $u['estado'] ?? 'activo', $u['fecha_creacion'] ?? date('Y-m-d H:i:s'),
                    ]);
                    $userMap[(int) $u['id']] = ['id' => (int) $this->pdo->lastInsertId(), 'rol' => 'estudiante'];
                } else {
                    $userMap[(int) $u['id']] = ['id' => -1, 'rol' => 'estudiante'];
                }
                $createdStudents++;
            }
            $report[] = ['Estudiantes nuevos migrados', $createdStudents];

            // 2. Mapa franja heredada → franja nueva (por docente, materia, día y hora de inicio).
            $slotMap = [];
            $findSlot = $this->pdo->prepare('SELECT id FROM franjas_disponibilidad WHERE docente_id = ? AND materia_id = ? AND dia_semana = ? AND hora_inicio = ?');
            $legacySlotById = [];
            foreach ($legacySlots as $s) {
                $legacySlotById[(int) $s['id']] = $s;
                $teacher = $userMap[(int) $s['docente_id']]['id'] ?? null;
                if ($teacher === null) {
                    continue;
                }
                $findSlot->execute([$teacher, (int) $s['materia_id'], self::DAYS[$s['dia_semana']] ?? 0, $s['hora_inicio']]);
                $id = $findSlot->fetchColumn();
                if ($id !== false) {
                    $slotMap[(int) $s['id']] = (int) $id;
                }
            }

            // 3. Tutorías con validación de integridad.
            $insert = $this->pdo->prepare(
                "INSERT INTO tutorias (codigo, estudiante_id, docente_id, materia_id, franja_id, fecha, hora_inicio, hora_fin, lugar, modalidad, tema, estado, creado_en)
                 SELECT ?, ?, f.docente_id, f.materia_id, f.id, ?, f.hora_inicio, f.hora_fin, f.lugar, f.modalidad, ?, ?, ? FROM franjas_disponibilidad f WHERE f.id = ?"
            );
            $seen = [];
            $accepted = 0;
            foreach ($legacyTutorings as $t) {
                $reasons = [];
                $slot = $legacySlotById[(int) $t['disponibilidad_id']] ?? null;
                $student = $userMap[(int) $t['estudiante_id']] ?? null;
                if ($slot === null) {
                    $reasons[] = 'La franja (disponibilidad_id) no existe';
                } else {
                    if ((int) $slot['docente_id'] !== (int) $t['docente_id']) {
                        $reasons[] = 'El docente no coincide con el de la franja';
                    }
                    if ((int) $slot['materia_id'] !== (int) $t['materia_id']) {
                        $reasons[] = 'La materia no coincide con la de la franja';
                    }
                    if ((int) date('N', (int) strtotime((string) $t['fecha_tutoria'])) !== (self::DAYS[$slot['dia_semana']] ?? 0)) {
                        $reasons[] = 'La fecha no cae en el día de la franja';
                    }
                    if (!isset($slotMap[(int) $t['disponibilidad_id']])) {
                        $reasons[] = 'La franja no existe en el nuevo sistema';
                    }
                }
                if ($student === null || $student['rol'] !== 'estudiante') {
                    $reasons[] = 'El estudiante no existe o no tiene rol estudiante';
                }
                $key = ($student['id'] ?? 0) . '|' . $t['disponibilidad_id'] . '|' . $t['fecha_tutoria'];
                if (isset($seen[$key])) {
                    $reasons[] = 'Reserva duplicada (mismo estudiante, franja y fecha)';
                }
                if ($reasons !== []) {
                    $rejected[] = [$t['id'], $t['fecha_tutoria'], $t['estado'], implode(' | ', $reasons)];
                    continue;
                }
                $seen[$key] = true;
                if (!$dryRun) {
                    $insert->execute([
                        'LEG-' . str_pad((string) $t['id'], 6, '0', STR_PAD_LEFT),
                        $student['id'],
                        $t['fecha_tutoria'],
                        trim((string) ($t['tema_tratar'] ?? '')) ?: 'Sin tema registrado (migrado)',
                        in_array($t['estado'], ['programada', 'completada', 'cancelada'], true) ? $t['estado'] : 'completada',
                        $t['fecha_solicitud'] ?? date('Y-m-d H:i:s'),
                        $slotMap[(int) $t['disponibilidad_id']],
                    ]);
                }
                $accepted++;
            }
            $report[] = ['Tutorías aceptadas', $accepted];
            $report[] = ['Tutorías rechazadas', count($rejected)];

            $dryRun ? $this->pdo->rollBack() : $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        $byReason = [];
        foreach ($rejected as $r) {
            foreach (explode(' | ', $r[3]) as $reason) {
                $byReason[$reason] = ($byReason[$reason] ?? 0) + 1;
            }
        }
        arsort($byReason);

        $out('');
        $out('REPORTE DE CONCILIACIÓN' . ($dryRun ? ' (simulación, no se escribió nada)' : ''));
        foreach ($report as [$label, $value]) {
            $out(sprintf('  %-32s %6d', $label, $value));
        }
        $out('  Motivos de rechazo:');
        foreach ($byReason as $reason => $n) {
            $out(sprintf('    %-52s %5d', $reason, $n));
        }

        $dir = sys_get_temp_dir();
        $csv = $dir . '/conciliacion_tutorias_' . date('Ymd_His') . '.csv';
        $fh = fopen($csv, 'w');
        fputcsv($fh, ['id_heredado', 'fecha', 'estado', 'motivos'], ';', '"', '');
        foreach ($rejected as $r) {
            fputcsv($fh, $r, ';', '"', '');
        }
        fclose($fh);
        $out("  Detalle de rechazos: {$csv}");
    }

    /** @return list<array<string, string|null>> */
    private function rows(string $sql, string $table): array
    {
        $result = [];
        if (!preg_match_all('/INSERT INTO `' . preg_quote($table, '/') . '` \(([^)]*)\) VALUES\s*(.*?);\s*$/ms', $sql, $blocks, PREG_SET_ORDER)) {
            return [];
        }
        foreach ($blocks as $block) {
            $columns = array_map(static fn ($c) => trim($c, " `"), explode(',', $block[1]));
            foreach ($this->tuples($block[2]) as $values) {
                if (count($values) === count($columns)) {
                    $result[] = array_combine($columns, $values);
                }
            }
        }

        return $result;
    }

    /** @return list<list<string|null>> */
    private function tuples(string $values): array
    {
        $tuples = [];
        $current = [];
        $token = '';
        $inString = false;
        $depth = 0;
        $quoted = false;
        $len = strlen($values);
        for ($i = 0; $i < $len; $i++) {
            $c = $values[$i];
            if ($inString) {
                if ($c === '\\') {
                    $token .= $values[++$i] ?? '';
                } elseif ($c === "'" && ($values[$i + 1] ?? '') === "'") {
                    $token .= "'";
                    $i++;
                } elseif ($c === "'") {
                    $inString = false;
                } else {
                    $token .= $c;
                }
                continue;
            }
            if ($c === "'") {
                $inString = true;
                $quoted = true;
            } elseif ($c === '(' && $depth === 0) {
                $depth = 1;
                $current = [];
                $token = '';
                $quoted = false;
            } elseif (($c === ',' || $c === ')') && $depth === 1) {
                $value = trim($token);
                $current[] = (!$quoted && strtoupper($value) === 'NULL') ? null : $value;
                $token = '';
                $quoted = false;
                if ($c === ')') {
                    $depth = 0;
                    $tuples[] = $current;
                }
            } elseif ($depth === 1) {
                $token .= $c;
            }
        }

        return $tuples;
    }
}
