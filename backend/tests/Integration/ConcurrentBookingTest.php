<?php

declare(strict_types=1);

namespace Tutorias\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use Tutorias\Tests\Support\ApiTestCase;
use Tutorias\Tests\Support\Fixtures;

/**
 * Escenario de calidad R2 / criterio de aceptación de T28:
 * "Prueba automatizada que impide doble reserva concurrente".
 * Lanza procesos PHP reales en paralelo contra MySQL; no es una simulación.
 */
final class ConcurrentBookingTest extends ApiTestCase
{
    private const PROCESSES = 12;

    #[Test]
    public function muchos_estudiantes_compitiendo_por_el_ultimo_cupo_solo_uno_lo_obtiene(): void
    {
        // Franja C: miércoles 14:00, cupo 1. Se crean 12 estudiantes que la piden al mismo tiempo.
        $students = [];
        $stmt = $this->pdo->prepare("INSERT INTO usuarios (id_institucional, nombre, email, password_hash, rol, estado) VALUES (?, ?, ?, 'x', 'estudiante', 'activo')");
        for ($i = 0; $i < self::PROCESSES; $i++) {
            $stmt->execute(["CONC{$i}", "Concurrente {$i}", "concurrente{$i}@uniminuto.edu.co"]);
            $students[] = (int) $this->pdo->lastInsertId();
        }

        $results = $this->race(array_map(fn (int $id) => [$id, Fixtures::SLOT_C, '2027-03-03'], $students));

        self::assertSame(1, count(array_keys($results, 'OK', true)), 'Resultados: ' . implode(', ', $results));
        self::assertSame(self::PROCESSES - 1, count(array_keys($results, 'slot_full', true)), 'Resultados: ' . implode(', ', $results));
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM tutorias WHERE franja_id = 3 AND fecha = '2027-03-03' AND estado = 'programada'")->fetchColumn());
    }

    #[Test]
    public function doble_clic_del_mismo_estudiante_no_crea_dos_reservas(): void
    {
        $requests = array_fill(0, 6, [Fixtures::STUDENT_LAURA, Fixtures::SLOT_A, '2027-03-08']);
        $results = $this->race($requests);

        self::assertSame(1, count(array_keys($results, 'OK', true)), 'Resultados: ' . implode(', ', $results));
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM tutorias WHERE estudiante_id = ' . Fixtures::STUDENT_LAURA)->fetchColumn());
    }

    /**
     * @param list<array{int, int, string}> $requests
     * @return list<string>
     */
    private function race(array $requests): array
    {
        $barrier = microtime(true) + 1.5;
        $script = __DIR__ . '/../Support/concurrent_booking.php';
        $procs = [];
        foreach ($requests as [$student, $slot, $date]) {
            $cmd = [PHP_BINARY, $script, (string) $student, (string) $slot, $date, (string) $barrier];
            $procs[] = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            $outputs[] = $pipes;
        }
        $results = [];
        foreach ($procs as $i => $proc) {
            $results[] = trim((string) stream_get_contents($outputs[$i][1]) . stream_get_contents($outputs[$i][2]));
            proc_close($proc);
        }

        return $results;
    }
}
