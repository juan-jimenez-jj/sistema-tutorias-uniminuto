<?php

declare(strict_types=1);

namespace Tutorias\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use Tutorias\Tests\Support\ApiTestCase;
use Tutorias\Tests\Support\Fixtures;

final class AdminApiTest extends ApiTestCase
{
    #[Test]
    public function administra_usuarios_y_materias(): void
    {
        $admin = $this->login('admin@uniminuto.edu.co');

        $created = $this->request('POST', '/api/v1/admin/usuarios', [
            'idInstitucional' => 'DOC099', 'nombre' => 'Carlos Docente', 'email' => 'carlos@uniminuto.edu.co',
            'password' => 'Inicial2027', 'rol' => 'docente', 'programaAcademico' => 'Física',
        ], $admin);
        $this->assertStatus(201, $created);
        $id = $this->json($created)['usuario']['id'];

        $this->assertStatus(204, $this->request('PUT', "/api/v1/admin/docentes/{$id}/materias", ['materiaIds' => [3, 19]], $admin));
        $teachers = $this->json($this->request('GET', '/api/v1/materias/3/docentes', null, $admin));
        self::assertSame('Carlos Docente', $teachers['items'][0]['nombre']);

        $list = $this->json($this->request('GET', '/api/v1/admin/usuarios?rol=docente&q=carlos', null, $admin));
        self::assertSame(1, $list['total']);
        self::assertSame(['Física Eléctrica', 'Física Mecánica'], $list['items'][0]['materias']);

        $subject = $this->request('POST', '/api/v1/admin/materias', ['codigo' => 'EST201', 'nombre' => 'Probabilidad', 'area' => 'Estadística'], $admin);
        $this->assertStatus(201, $subject);
        $this->assertStatus(409, $this->request('POST', '/api/v1/admin/materias', ['codigo' => 'EST201', 'nombre' => 'Otra', 'area' => 'Estadística'], $admin));
    }

    #[Test]
    public function desactivar_un_usuario_le_impide_entrar_y_no_puede_desactivarse_a_si_mismo(): void
    {
        $admin = $this->login('admin@uniminuto.edu.co');
        $mateo = $this->login('mateo@uniminuto.edu.co');
        $this->assertStatus(200, $this->request('PATCH', '/api/v1/admin/usuarios/' . Fixtures::STUDENT_MATEO . '/estado', ['estado' => 'inactivo'], $admin));
        // La sesión ya abierta se revoca de inmediato, sin esperar a que expire el JWT.
        $this->assertStatus(401, $this->request('GET', '/api/v1/tutorias', null, $mateo));
        $this->assertStatus(401, $this->request('POST', '/api/v1/auth/login', ['email' => 'mateo@uniminuto.edu.co', 'password' => Fixtures::PASSWORD]));
        $this->assertStatus(422, $this->request('PATCH', '/api/v1/admin/usuarios/' . Fixtures::ADMIN . '/estado', ['estado' => 'inactivo'], $admin));
    }

    #[Test]
    public function los_indicadores_usan_las_mismas_definiciones_que_el_panel_del_docente(): void
    {
        $laura = $this->login('laura@uniminuto.edu.co');
        $mateo = $this->login('mateo@uniminuto.edu.co');
        $sara = $this->login('sara@uniminuto.edu.co');

        $attended = $this->book($laura, Fixtures::SLOT_A, '2027-03-08');
        $cancelled = $this->book($mateo, Fixtures::SLOT_A, '2027-03-08');
        $this->assertStatus(200, $this->request('POST', "/api/v1/tutorias/{$cancelled['id']}/cancelacion", [], $mateo));
        $this->book($sara, Fixtures::SLOT_C, '2027-03-10');        // pasará sin que nadie registre la asistencia
        $this->book($laura, Fixtures::SLOT_A, '2027-03-15');       // sigue siendo futura

        $this->clock->travelTo('2027-03-08 11:05');
        $this->assertStatus(200, $this->request('POST', "/api/v1/tutorias/{$attended['id']}/cierre", ['asistio' => true], $this->login('ana@uniminuto.edu.co')));

        $this->clock->travelTo('2027-03-11 09:00');
        $report = $this->json($this->request('GET', '/api/v1/admin/reportes?desde=2027-03-01&hasta=2027-03-31', null, $this->login('admin@uniminuto.edu.co')));
        $t = $report['totales'];

        self::assertSame(4, $t['tutorias']);
        self::assertSame(1, $t['completadas']);
        self::assertSame(1, $t['canceladas']);
        self::assertSame(1, $t['programadas'], 'Solo cuenta las futuras');
        self::assertSame(1, $t['pendientesCierre'], 'Ya pasó y nadie registró la asistencia');
        self::assertSame(1, $t['estudiantesAtendidos'], 'Solo quien completó una tutoría');
        self::assertSame(3, $t['estudiantesConReserva']);
        self::assertLessThanOrEqual($t['completadas'], $t['estudiantesAtendidos']);

        $ana = $report['porDocente'][0];
        self::assertSame(1, $ana['estudiantes']);
        self::assertSame(1, $ana['pendientesCierre']);
        self::assertSame(['mes' => '2027-03', 'completadas' => 1, 'canceladas' => 1, 'programadas' => 1, 'pendientesCierre' => 1, 'inasistencias' => 0], $report['porMes'][0]);

        // El panel del docente y el reporte del coordinador coinciden.
        $stats = $this->json($this->request('GET', '/api/v1/tutorias/estadisticas', null, $this->login('ana@uniminuto.edu.co')));
        self::assertSame($stats['estudiantesAtendidos'], $ana['estudiantes']);
        self::assertSame($stats['pendientesCierre'], $ana['pendientesCierre']);
    }

    #[Test]
    public function reportes_y_exportacion_csv(): void
    {
        $t = $this->book($this->login('laura@uniminuto.edu.co'), Fixtures::SLOT_A, '2027-03-08', '=HYPERLINK("http://malicioso")');
        $admin = $this->login('admin@uniminuto.edu.co');

        $report = $this->json($this->request('GET', '/api/v1/admin/reportes?desde=2027-03-01&hasta=2027-03-31', null, $admin));
        self::assertSame(1, $report['totales']['tutorias']);
        self::assertSame('Cálculo Diferencial', $report['porMateria'][0]['materia']);
        self::assertSame(1, $report['mapaCalor'][0]['diaSemana']);

        $csv = $this->request('GET', '/api/v1/admin/reportes/tutorias.csv?desde=2027-03-01&hasta=2027-03-31', null, $admin);
        $this->assertStatus(200, $csv);
        self::assertStringContainsString('text/csv', $csv->getHeaderLine('Content-Type'));
        self::assertStringContainsString($t['codigo'], (string) $csv->getBody());
        // Protección contra inyección de fórmulas en Excel.
        self::assertStringContainsString("'=HYPERLINK", (string) $csv->getBody());

        $this->assertStatus(422, $this->request('GET', '/api/v1/admin/reportes?desde=2027-03-31&hasta=2027-03-01', null, $admin));
    }

    #[Test]
    public function resumen_publico_sin_autenticacion(): void
    {
        $data = $this->json($this->request('GET', '/api/v1/publico/resumen'));
        self::assertSame(24, $data['materias']);
        self::assertSame(2, $data['docentes']);
    }

    #[Test]
    public function health_y_metricas(): void
    {
        $this->assertStatus(200, $this->request('GET', '/health'));
        $ready = $this->json($this->request('GET', '/health/ready'));
        self::assertSame('ok', $ready['checks']['database']['status']);
        self::assertStringContainsString('usuarios_activos{rol="estudiante"} 3', (string) $this->request('GET', '/metrics')->getBody());
        $this->assertStatus(404, $this->request('GET', '/api/v1/no-existe', null, $this->login('admin@uniminuto.edu.co')));
    }
}
