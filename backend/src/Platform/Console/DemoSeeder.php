<?php

declare(strict_types=1);

namespace Tutorias\Platform\Console;

use Tutorias\Identity\Domain\PasswordHasher;
use Tutorias\Notifications\Application\TutoringNotifier;

/**
 * Carga los datos reales de la sede (docentes, materias que dictan y sus franjas, migrados del
 * sistema heredado) y genera un historial de tutorías verosímil y COHERENTE con las reglas
 * (cupos, días de la franja, sin cruces), relativo a la fecha actual para que la demo siempre
 * tenga tutorías próximas y pasadas.
 */
final class DemoSeeder
{
    public const ADMIN_PASSWORD = 'Admin2026*';
    public const TEACHER_PASSWORD = 'Docente2026*';
    public const STUDENT_PASSWORD = 'Estudiante2026*';
    public const DEMO_STUDENT_EMAIL = 'estudiante@uniminuto.edu.co';

    private const PROGRAMS = [
        'Ingeniería de Sistemas' => 30, 'Ingeniería Industrial' => 18, 'Ingeniería Civil' => 10, 'Administración de Empresas' => 14,
        'Contaduría Pública' => 12, 'Psicología' => 6, 'Licenciatura en Matemáticas' => 6, 'Administración Financiera' => 4,
    ];
    private const FIRST = ['Valentina', 'Santiago', 'Mariana', 'Sebastián', 'Camila', 'Mateo', 'Daniela', 'Nicolás', 'Laura', 'Samuel', 'Sofía',
        'Juan Pablo', 'Isabella', 'Alejandro', 'Gabriela', 'David', 'Paula', 'Andrés', 'Natalia', 'Felipe', 'Juliana', 'Tomás', 'Sara', 'Diego',
        'Manuela', 'Esteban', 'Luisa', 'Kevin', 'Ana María', 'Cristian', 'Lina', 'Brayan', 'Angie', 'Jhon', 'Karen', 'Miguel Ángel', 'Yuliana', 'Óscar'];
    private const LAST = ['Rodríguez', 'Gómez', 'González', 'Martínez', 'García', 'López', 'Hernández', 'Sánchez', 'Ramírez', 'Pérez', 'Torres',
        'Díaz', 'Moreno', 'Vargas', 'Rojas', 'Castro', 'Suárez', 'Forero', 'Cárdenas', 'Bernal', 'Cortés', 'Garzón', 'Pinzón', 'Cubillos',
        'Rincón', 'Chaparro', 'Ballesteros', 'Murcia', 'Sierra', 'Neira', 'Bohórquez', 'Ávila', 'Peña', 'Quintero', 'Mahecha', 'Cifuentes'];
    private const TOPICS = [
        'MAT' => ['Límites y continuidad', 'Regla de la cadena', 'Derivadas implícitas', 'Optimización con derivadas', 'Integración por partes',
            'Sustitución trigonométrica', 'Fracciones parciales', 'Áreas entre curvas', 'Series y criterios de convergencia', 'Funciones y gráficas',
            'Ecuaciones de primer orden', 'Transformada de Laplace', 'Integrales dobles', 'Interés compuesto y anualidades', 'Programación lineal: método gráfico',
            'Tablas de verdad y cuantificadores', 'Inducción matemática', 'Grafos y árboles', 'Método de Newton-Raphson', 'Preparación para el parcial'],
        'ALG' => ['Sistemas de ecuaciones y Gauss-Jordan', 'Determinantes', 'Espacios vectoriales', 'Valores y vectores propios', 'Transformaciones lineales'],
        'FIS' => ['Cinemática en dos dimensiones', 'Leyes de Newton', 'Trabajo y energía', 'Ley de Coulomb', 'Circuitos RC', 'Ley de Ohm y Kirchhoff',
            'Principio de Bernoulli', 'Primera ley de la termodinámica', 'Ondas estacionarias', 'Laboratorio: análisis de datos'],
        'QUI' => ['Estequiometría', 'Balanceo de ecuaciones redox', 'Soluciones y concentraciones', 'Enlace químico', 'Metabolismo y enzimas', 'Propiedades de los polímeros'],
    ];
    private const COMMENTS = [
        5 => ['Excelente explicación, por fin entendí el tema.', 'Muy paciente y claro. ¡Gracias!', 'Me ayudó muchísimo para el parcial.', 'Los ejercicios resueltos fueron clave.'],
        4 => ['Buena tutoría, me quedaron claros los conceptos.', 'Muy útil, aunque el tiempo se quedó corto.', 'Buenas explicaciones con ejemplos.'],
        3 => ['Aclaró algunas dudas, pero fue algo rápido.', 'Bien, aunque había mucho ruido en el salón.'],
        2 => ['Faltó tiempo para resolver mis dudas.'],
        1 => ['El docente llegó tarde y no alcanzamos a ver el tema.'],
    ];
    private const NOTES = ['Se resolvieron los ejercicios del taller 3.', 'Se recomienda practicar la guía de ejercicios propuestos.',
        'El estudiante llegó con dudas puntuales; buen avance.', 'Se reforzaron conceptos previos.', 'Se dejó material de apoyo en el aula virtual.'];

    /** @var array<string, list<array{int, int}>> agenda de cada estudiante por fecha (para evitar cruces) */
    private array $agenda = [];

    public function __construct(private readonly \PDO $pdo, private readonly PasswordHasher $hasher, private readonly string $seedDir)
    {
    }

    public function run(callable $out): void
    {
        if ((int) $this->pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0) {
            $out('• La base de datos ya tiene usuarios; no se cargan datos de demostración (usa db:reset para empezar de cero).');

            return;
        }
        mt_srand(2027);
        $legacy = require $this->seedDir . '/legacy_catalogo.php';
        $now = new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota'));

        $this->pdo->beginTransaction();
        try {
            $this->seedStaff($legacy, $out);
            $students = $this->seedStudents($out);
            $this->seedTutorings($students, $now, $out);
            $this->seedNotifications($now);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        $out('');
        $out('Credenciales de demostración:');
        $out('  Administrador  admin@uniminuto.edu.co          ' . self::ADMIN_PASSWORD);
        $out('  Docente        andres.rozo@uniminuto.edu.co    ' . self::TEACHER_PASSWORD . '  (aplica a todos los docentes)');
        $out('  Estudiante     ' . self::DEMO_STUDENT_EMAIL . '     ' . self::STUDENT_PASSWORD);
    }

    /** @param array<string, list<mixed>> $legacy */
    private function seedStaff(array $legacy, callable $out): void
    {
        $insert = $this->pdo->prepare(
            'INSERT INTO usuarios (id, id_institucional, nombre, email, password_hash, rol, programa_academico, estado, creado_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, \'activo\', NOW() - INTERVAL 200 DAY)'
        );
        $insert->execute([1, 'ADMIN001', 'Coordinación Ciencias Básicas', 'admin@uniminuto.edu.co', $this->hasher->hash(self::ADMIN_PASSWORD), 'administrador', 'Coordinación académica']);
        $teacherHash = $this->hasher->hash(self::TEACHER_PASSWORD);
        foreach ($legacy['docentes'] as $d) {
            $insert->execute([$d['id'], $d['id_institucional'], $d['nombre'], $d['email'], $teacherHash, 'docente', $d['area']]);
        }
        $dm = $this->pdo->prepare('INSERT INTO docente_materia (docente_id, materia_id) VALUES (?, ?)');
        foreach ($legacy['docente_materia'] as [$teacher, $subject]) {
            $dm->execute([$teacher, $subject]);
        }
        // El sistema heredado permitía que un docente tuviera franjas superpuestas (una por materia
        // a la misma hora). El nuevo dominio lo prohíbe: de cada grupo superpuesto queda publicada una
        // franja (rotando la materia según el día) y las demás se migran pausadas, conservando su historial.
        $groups = [];
        foreach ($legacy['franjas'] as $i => $f) {
            $groups[$f['docente_id'] . '|' . $f['dia'] . '|' . $f['inicio']][] = $i;
        }
        $slot = $this->pdo->prepare(
            'INSERT INTO franjas_disponibilidad (docente_id, materia_id, dia_semana, hora_inicio, hora_fin, lugar, modalidad, cupo, activa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $paused = 0;
        foreach ($legacy['franjas'] as $i => $f) {
            $group = $groups[$f['docente_id'] . '|' . $f['dia'] . '|' . $f['inicio']];
            $active = count($group) === 1 || $group[$f['dia'] % count($group)] === $i;
            $paused += $active ? 0 : 1;
            $minutes = (strtotime($f['fin']) - strtotime($f['inicio'])) / 60;
            $virtual = $active && $i % 7 === 3; // algunas franjas pasan a modalidad virtual
            $slot->execute([
                $f['docente_id'], $f['materia_id'], $f['dia'], $f['inicio'], $f['fin'],
                $virtual ? 'Microsoft Teams' : $f['lugar'],
                $virtual ? 'virtual' : 'presencial',
                $minutes <= 45 ? 2 : 4,
                $active ? 1 : 0,
            ]);
        }
        $out(sprintf(
            '✓ %d docentes, %d asignaciones docente-materia y %d franjas migradas del sistema heredado (%d superpuestas quedaron pausadas).',
            count($legacy['docentes']),
            count($legacy['docente_materia']),
            count($legacy['franjas']),
            $paused
        ));
    }

    /** @return list<int> ids de estudiantes; el primero es el estudiante de demostración */
    private function seedStudents(callable $out): array
    {
        $hash = $this->hasher->hash(self::STUDENT_PASSWORD);
        $insert = $this->pdo->prepare(
            "INSERT INTO usuarios (id_institucional, nombre, email, password_hash, rol, programa_academico, estado, consentimiento_datos_en, creado_en)
             VALUES (?, ?, ?, ?, 'estudiante', ?, 'activo', ?, ?)"
        );
        $insert->execute(['000845123', 'Valentina Rodríguez Castro', self::DEMO_STUDENT_EMAIL, $hash, 'Ingeniería de Sistemas', '2026-02-02 08:15:00', '2026-02-02 08:15:00']);
        $ids = [(int) $this->pdo->lastInsertId()];

        $programs = [];
        foreach (self::PROGRAMS as $program => $weight) {
            array_push($programs, ...array_fill(0, $weight, $program));
        }
        $used = [];
        for ($i = 0; count($ids) < 160; $i++) {
            $first = self::FIRST[mt_rand(0, count(self::FIRST) - 1)];
            $last1 = self::LAST[mt_rand(0, count(self::LAST) - 1)];
            $last2 = self::LAST[mt_rand(0, count(self::LAST) - 1)];
            $email = $this->slug(explode(' ', $first)[0]) . '.' . $this->slug($last1) . ($i > 0 ? mt_rand(1, 99) : '') . '@uniminuto.edu.co';
            if (isset($used[$email])) {
                continue;
            }
            $used[$email] = true;
            $created = (new \DateTimeImmutable('-' . mt_rand(120, 400) . ' days'))->format('Y-m-d H:i:s');
            $insert->execute([sprintf('000%06d', 700000 + $i * 37), "{$first} {$last1} {$last2}", $email, $hash, $programs[mt_rand(0, count($programs) - 1)], $created, $created]);
            $ids[] = (int) $this->pdo->lastInsertId();
        }
        $out('✓ ' . count($ids) . ' estudiantes de demostración (todos con autorización de datos registrada).');

        return $ids;
    }

    /** @param list<int> $students */
    private function seedTutorings(array $students, \DateTimeImmutable $now, callable $out): void
    {
        $slots = $this->pdo->query('SELECT f.*, m.codigo AS materia_codigo FROM franjas_disponibilidad f JOIN materias m ON m.id = f.materia_id')->fetchAll();
        $insert = $this->pdo->prepare(
            'INSERT INTO tutorias (codigo, estudiante_id, docente_id, materia_id, franja_id, fecha, hora_inicio, hora_fin, lugar, modalidad, tema, estado,
                cancelada_por, motivo_cancelacion, cancelada_en, observaciones_docente, completada_en, calificacion, comentario_estudiante, calificada_en, creado_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $demo = $students[0];
        $demoPast = 0;
        $demoFuture = 0;
        $count = 0;
        $codes = [];
        $today = $now->setTime(0, 0);

        for ($date = $today->modify('-150 days'); $date <= $today->modify('+21 days'); $date = $date->modify('+1 day')) {
            $weekday = (int) $date->format('N');
            $ymd = $date->format('Y-m-d');
            foreach ($slots as $s) {
                if ((int) $s['dia_semana'] !== $weekday) {
                    continue;
                }
                $start = substr((string) $s['hora_inicio'], 0, 5);
                $end = substr((string) $s['hora_fin'], 0, 5);
                $startsAt = new \DateTimeImmutable("{$ymd} {$start}", $now->getTimezone());
                $future = $startsAt > $now;
                if ($future && !(bool) $s['activa']) {
                    continue; // las franjas pausadas no reciben reservas nuevas
                }
                $capacity = (int) $s['cupo'];
                // Más demanda en semanas de parciales (aprox. semanas 8 y 16 del semestre).
                $week = (int) $date->format('W');
                $peak = in_array($week % 16, [7, 8, 15, 0], true) ? 1 : 0;
                $n = $future ? (mt_rand(0, 100) < 45 ? mt_rand(1, max(1, $capacity - 1)) : 0) : min($capacity, max(0, mt_rand(-1, $capacity) + $peak));
                if ($future && $date > $today->modify('+10 days')) {
                    $n = min($n, 1);
                }

                for ($k = 0; $k < $n; $k++) {
                    $student = null;
                    $wantsDemo = ($future ? $demoFuture < 3 : ($demoPast < 9 && $date >= $today->modify('-75 days'))) && mt_rand(0, 1000) < ($future ? 60 : 22);
                    if ($wantsDemo && $this->free($demo, $ymd, $start, $end)) {
                        $student = $demo;
                    }
                    for ($tries = 0; $student === null && $tries < 15; $tries++) {
                        $candidate = $students[mt_rand(1, count($students) - 1)];
                        if ($this->free($candidate, $ymd, $start, $end)) {
                            $student = $candidate;
                        }
                    }
                    if ($student === null) {
                        continue;
                    }
                    $this->agenda[$student . '|' . $ymd][] = [$this->min($start), $this->min($end)];

                    $row = $this->buildTutoring($s, $student, $ymd, $start, $end, $startsAt, $now, $future);
                    do {
                        $code = 'TUT-' . substr(str_shuffle('23456789ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 6);
                    } while (isset($codes[$code]));
                    $codes[$code] = true;
                    $insert->execute([$code, ...$row]);
                    $count++;
                    if ($student === $demo) {
                        $future && $row[10] === 'programada' ? $demoFuture++ : $demoPast++;
                    }
                }
            }
        }
        // La tutoría completada más reciente del estudiante demo queda sin calificar, para mostrar ese flujo.
        $this->pdo->exec("UPDATE tutorias SET calificacion = NULL, comentario_estudiante = NULL, calificada_en = NULL
            WHERE estudiante_id = {$demo} AND estado = 'completada' ORDER BY fecha DESC LIMIT 1");
        $out("✓ {$count} tutorías generadas (últimos 5 meses y próximas 3 semanas).");
    }

    /** @param array<string, mixed> $s @return list<mixed> */
    private function buildTutoring(array $s, int $student, string $ymd, string $start, string $end, \DateTimeImmutable $startsAt, \DateTimeImmutable $now, bool $future): array
    {
        $prefix = substr((string) $s['materia_codigo'], 0, 3);
        $topics = self::TOPICS[$prefix] ?? self::TOPICS['MAT'];
        $topic = $topics[mt_rand(0, count($topics) - 1)];
        $created = $startsAt->modify('-' . mt_rand(1, 9) . ' days')->modify('-' . mt_rand(0, 600) . ' minutes');
        if ($created > $now) {
            $created = $now->modify('-' . mt_rand(5, 300) . ' minutes');
        }
        $fmt = static fn (?\DateTimeImmutable $d) => $d?->format('Y-m-d H:i:s');

        $status = 'programada';
        $cancelledBy = $reason = $cancelledAt = $notes = $completedAt = $rating = $comment = $ratedAt = null;
        $roll = mt_rand(1, 100);
        $recent = $startsAt > $now->modify('-3 days');

        if ($future) {
            if ($roll <= 10) {
                [$status, $cancelledBy, $reason] = ['cancelada', 'estudiante', 'Cancelada por el estudiante'];
                $cancelledAt = $created->modify('+1 day') < $now ? $created->modify('+1 day') : $now;
            }
        } elseif ($recent && $roll <= 35) {
            $status = 'programada'; // pendiente de cierre por el docente
        } elseif ($roll <= 72) {
            $status = 'completada';
            $completedAt = $startsAt->modify('+' . mt_rand(40, 180) . ' minutes');
            $notes = mt_rand(0, 100) < 60 ? self::NOTES[mt_rand(0, count(self::NOTES) - 1)] : null;
            if (mt_rand(0, 100) < 78) {
                $r = mt_rand(1, 100);
                $rating = $r <= 3 ? 1 : ($r <= 8 ? 2 : ($r <= 22 ? 3 : ($r <= 58 ? 4 : 5)));
                $comment = mt_rand(0, 100) < 55 ? self::COMMENTS[$rating][mt_rand(0, count(self::COMMENTS[$rating]) - 1)] : null;
                $ratedAt = $completedAt->modify('+' . mt_rand(1, 48) . ' hours');
            }
        } elseif ($roll <= 90) {
            $status = 'cancelada';
            $byTeacher = mt_rand(0, 100) < 30;
            $cancelledBy = $byTeacher ? 'docente' : 'estudiante';
            $reason = $byTeacher
                ? ['Reunión de facultad en el mismo horario.', 'Incapacidad médica.', 'Salida académica con el grupo.'][mt_rand(0, 2)]
                : 'Cancelada por el estudiante';
            $cancelledAt = $startsAt->modify('-' . mt_rand(4, 48) . ' hours');
        } else {
            $status = 'no_asistio';
            $completedAt = $startsAt->modify('+30 minutes');
        }

        return [
            $student, (int) $s['docente_id'], (int) $s['materia_id'], (int) $s['id'], $ymd, $start, $end, $s['lugar'], $s['modalidad'], $topic, $status,
            $cancelledBy, $reason, $fmt($cancelledAt), $notes, $fmt($completedAt), $rating, $comment, $fmt($ratedAt), $fmt($created),
        ];
    }

    private function seedNotifications(\DateTimeImmutable $now): void
    {
        $demo = (int) $this->pdo->query("SELECT id FROM usuarios WHERE email = '" . self::DEMO_STUDENT_EMAIL . "'")->fetchColumn();
        $next = $this->pdo->query("SELECT t.codigo, t.fecha, t.hora_inicio, m.nombre AS materia, d.nombre AS docente FROM tutorias t
            JOIN materias m ON m.id = t.materia_id JOIN usuarios d ON d.id = t.docente_id
            WHERE t.estudiante_id = {$demo} AND t.estado = 'programada' AND t.fecha >= CURDATE() ORDER BY t.fecha LIMIT 1")->fetch();
        $insert = $this->pdo->prepare('INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, enlace, leida_en, creado_en) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $insert->execute([$demo, 'bienvenida', 'Bienvenida al nuevo Sistema de Tutorías', 'Ahora puedes reservar, cancelar y calificar tus tutorías de Ciencias Básicas desde cualquier dispositivo.', '/estudiante', $now->modify('-20 days')->format('Y-m-d H:i:s'), $now->modify('-21 days')->format('Y-m-d H:i:s')]);
        if ($next) {
            $insert->execute([$demo, 'reserva_confirmada', "Tutoría confirmada · {$next['materia']}", "Tu tutoría {$next['codigo']} con {$next['docente']} quedó programada para el " . TutoringNotifier::humanDate((string) $next['fecha']) . ' a las ' . TutoringNotifier::hour12((string) $next['hora_inicio']), '/estudiante/tutorias', null, $now->modify('-2 hours')->format('Y-m-d H:i:s')]);
        }
    }

    private function free(int $student, string $ymd, string $start, string $end): bool
    {
        [$s, $e] = [$this->min($start), $this->min($end)];
        foreach ($this->agenda[$student . '|' . $ymd] ?? [] as [$os, $oe]) {
            if ($s < $oe && $os < $e) {
                return false;
            }
        }

        return true;
    }

    private function min(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $h * 60 + $m;
    }

    private function slug(string $text): string
    {
        $map = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n'];

        return strtolower(strtr($text, $map));
    }
}
