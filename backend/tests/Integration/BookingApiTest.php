<?php

declare(strict_types=1);

namespace Tutorias\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use Tutorias\Shared\Infrastructure\Outbox\OutboxProcessor;
use Tutorias\Tests\Support\ApiTestCase;
use Tutorias\Tests\Support\Fixtures;

final class BookingApiTest extends ApiTestCase
{
    #[Test]
    public function flujo_completo_reservar_completar_y_calificar(): void
    {
        $laura = $this->login('laura@uniminuto.edu.co');
        $ana = $this->login('ana@uniminuto.edu.co');

        // 1. El estudiante consulta ofertas y reserva.
        $offers = $this->json($this->request('GET', '/api/v1/ofertas?materiaId=1', null, $laura));
        $monday = array_values(array_filter($offers['dias'], fn ($d) => $d['fecha'] === '2027-03-08'))[0];
        self::assertSame(2, $monday['franjas'][0]['cuposDisponibles']);

        $t = $this->book($laura, Fixtures::SLOT_A, '2027-03-08', 'Límites al infinito');
        self::assertMatchesRegularExpression('/^TUT-[A-Z2-9]{6}$/', $t['codigo']);
        self::assertSame('programada', $t['estado']);

        // 2. La oferta refleja el cupo tomado.
        $offers = $this->json($this->request('GET', '/api/v1/ofertas?materiaId=1', null, $laura));
        $monday = array_values(array_filter($offers['dias'], fn ($d) => $d['fecha'] === '2027-03-08'))[0];
        self::assertSame(1, $monday['franjas'][0]['cuposDisponibles']);
        self::assertTrue($monday['franjas'][0]['yaReservada']);

        // 3. El docente la ve en su agenda y no puede cerrarla antes de iniciar.
        $agenda = $this->json($this->request('GET', '/api/v1/tutorias?alcance=proximas', null, $ana));
        self::assertSame($t['id'], $agenda['items'][0]['id']);
        $this->assertStatus(422, $this->request('POST', "/api/v1/tutorias/{$t['id']}/cierre", ['asistio' => true], $ana));

        // 4. Pasa la tutoría: el docente la completa y el estudiante la califica.
        // El token dura 8 horas: tras "viajar" una semana se inicia sesión de nuevo.
        $this->clock->travelTo('2027-03-08 11:05');
        $this->assertStatus(401, $this->request('GET', '/api/v1/auth/yo', null, $ana));
        [$laura, $ana] = [$this->login('laura@uniminuto.edu.co'), $this->login('ana@uniminuto.edu.co')];
        $this->assertStatus(200, $this->request('POST', "/api/v1/tutorias/{$t['id']}/cierre", ['asistio' => true, 'observaciones' => 'Buen avance'], $ana));
        $rated = $this->request('POST', "/api/v1/tutorias/{$t['id']}/calificacion", ['calificacion' => 5, 'comentario' => 'Excelente'], $laura);
        $this->assertStatus(200, $rated);
        self::assertSame(5, $this->json($rated)['tutoria']['calificacion']);
        $this->assertStatus(409, $this->request('POST', "/api/v1/tutorias/{$t['id']}/calificacion", ['calificacion' => 1], $laura));

        $stats = $this->json($this->request('GET', '/api/v1/tutorias/estadisticas', null, $laura));
        self::assertSame(1, $stats['completadas']);
        self::assertSame(5.0, $stats['calificacionPromedio']);
    }

    #[Test]
    public function cupo_lleno_y_cruce_de_horario(): void
    {
        $this->book($this->login('laura@uniminuto.edu.co'), Fixtures::SLOT_A, '2027-03-08');
        $this->book($this->login('mateo@uniminuto.edu.co'), Fixtures::SLOT_A, '2027-03-08');

        $sara = $this->login('sara@uniminuto.edu.co');
        $full = $this->request('POST', '/api/v1/tutorias', ['franjaId' => Fixtures::SLOT_A, 'fecha' => '2027-03-08', 'tema' => 'Derivadas'], $sara);
        $this->assertStatus(409, $full);
        self::assertSame('slot_full', $this->json($full)['code']);

        // Laura ya tiene A (10:00–11:00); B (10:30–11:30) se cruza.
        $overlap = $this->request('POST', '/api/v1/tutorias', ['franjaId' => Fixtures::SLOT_B, 'fecha' => '2027-03-08', 'tema' => 'Integrales'], $this->login('laura@uniminuto.edu.co'));
        $this->assertStatus(409, $overlap);
        self::assertSame('student_overlap', $this->json($overlap)['code']);
    }

    #[Test]
    public function regla_de_cancelacion_de_tres_horas(): void
    {
        $laura = $this->login('laura@uniminuto.edu.co');
        $t = $this->book($laura, Fixtures::SLOT_A, '2027-03-08');

        $this->clock->travelTo('2027-03-08 07:30'); // faltan 2 h 30 min
        $laura = $this->login('laura@uniminuto.edu.co');
        $late = $this->request('POST', "/api/v1/tutorias/{$t['id']}/cancelacion", [], $laura);
        $this->assertStatus(422, $late);
        self::assertSame('cancellation_window_closed', $this->json($late)['code']);

        $this->clock->travelTo('2027-03-08 06:59');
        $laura = $this->login('laura@uniminuto.edu.co');
        $ok = $this->request('POST', "/api/v1/tutorias/{$t['id']}/cancelacion", [], $laura);
        $this->assertStatus(200, $ok);
        self::assertSame('cancelada', $this->json($ok)['tutoria']['estado']);

        // Al cancelar se libera el cupo y la misma estudiante puede volver a reservar.
        $this->book($laura, Fixtures::SLOT_A, '2027-03-08');
    }

    #[Test]
    public function un_estudiante_no_ve_ni_cancela_tutorias_ajenas(): void
    {
        $t = $this->book($this->login('laura@uniminuto.edu.co'), Fixtures::SLOT_A, '2027-03-08');
        $mateo = $this->login('mateo@uniminuto.edu.co');

        $this->assertStatus(404, $this->request('GET', "/api/v1/tutorias/{$t['id']}", null, $mateo));
        $this->assertStatus(403, $this->request('POST', "/api/v1/tutorias/{$t['id']}/cancelacion", [], $mateo));
        self::assertSame(0, $this->json($this->request('GET', '/api/v1/tutorias', null, $mateo))['total']);
        // Un docente distinto tampoco puede cerrarla.
        $this->clock->travelTo('2027-03-08 11:00');
        $this->assertStatus(403, $this->request('POST', "/api/v1/tutorias/{$t['id']}/cierre", ['asistio' => true], $this->login('beto@uniminuto.edu.co')));
    }

    #[Test]
    public function la_reserva_genera_notificaciones_via_outbox(): void
    {
        $t = $this->book($this->login('laura@uniminuto.edu.co'), Fixtures::SLOT_A, '2027-03-08');
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM outbox_eventos WHERE tipo = 'tutoria.reservada' AND estado = 'pendiente'")->fetchColumn());

        $result = $this->container->get(OutboxProcessor::class)->processBatch();
        self::assertSame(['procesados' => 1, 'fallidos' => 0], $result);

        $laura = $this->json($this->request('GET', '/api/v1/notificaciones', null, $this->login('laura@uniminuto.edu.co')));
        self::assertSame(1, $laura['noLeidas']);
        self::assertStringContainsString($t['codigo'], $laura['items'][0]['mensaje']);
        $ana = $this->json($this->request('GET', '/api/v1/notificaciones', null, $this->login('ana@uniminuto.edu.co')));
        self::assertStringContainsString('Laura Estudiante', $ana['items'][0]['mensaje']);

        // Reprocesar el mismo evento no duplica notificaciones (consumidor idempotente).
        $this->pdo->exec("UPDATE outbox_eventos SET estado = 'pendiente'");
        $this->container->get(OutboxProcessor::class)->processBatch();
        self::assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM notificaciones')->fetchColumn());
    }

    #[Test]
    public function docente_cancela_con_motivo_y_auditoria_registra_la_accion(): void
    {
        $t = $this->book($this->login('laura@uniminuto.edu.co'), Fixtures::SLOT_A, '2027-03-08');
        $ana = $this->login('ana@uniminuto.edu.co');
        $this->assertStatus(422, $this->request('POST', "/api/v1/tutorias/{$t['id']}/cancelacion", ['motivo' => ''], $ana));
        $this->assertStatus(200, $this->request('POST', "/api/v1/tutorias/{$t['id']}/cancelacion", ['motivo' => 'Reunión de facultad'], $ana));

        $audit = $this->json($this->request('GET', '/api/v1/admin/auditoria?accion=tutoria.', null, $this->login('admin@uniminuto.edu.co')));
        self::assertSame(['tutoria.cancelada', 'tutoria.reservada'], array_column($audit['items'], 'accion'));
        self::assertSame('10.0.0.1', $audit['items'][0]['ip']);
    }
}
