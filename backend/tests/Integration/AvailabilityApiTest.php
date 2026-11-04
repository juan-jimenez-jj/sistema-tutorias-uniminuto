<?php

declare(strict_types=1);

namespace Tutorias\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use Tutorias\Tests\Support\ApiTestCase;
use Tutorias\Tests\Support\Fixtures;

final class AvailabilityApiTest extends ApiTestCase
{
    private function slotBody(array $override = []): array
    {
        return $override + ['materiaId' => 1, 'diaSemana' => 2, 'horaInicio' => '16:00', 'horaFin' => '17:30', 'lugar' => 'B201', 'modalidad' => 'presencial', 'cupo' => 3];
    }

    #[Test]
    public function docente_publica_franja_y_aparece_en_ofertas(): void
    {
        $ana = $this->login('ana@uniminuto.edu.co');
        $created = $this->request('POST', '/api/v1/docente/franjas', $this->slotBody(), $ana);
        $this->assertStatus(201, $created);
        self::assertSame('Martes', $this->json($created)['franja']['diaNombre']);

        $offers = $this->json($this->request('GET', '/api/v1/ofertas?materiaId=1', null, $this->login('laura@uniminuto.edu.co')));
        self::assertContains('2027-03-02', array_column($offers['dias'], 'fecha'));
    }

    #[Test]
    public function rechaza_franjas_que_se_cruzan_o_de_materias_no_asignadas(): void
    {
        $ana = $this->login('ana@uniminuto.edu.co');
        $overlap = $this->request('POST', '/api/v1/docente/franjas', $this->slotBody(['diaSemana' => 1, 'horaInicio' => '10:30', 'horaFin' => '11:30']), $ana);
        $this->assertStatus(409, $overlap);
        self::assertSame('slot_overlap', $this->json($overlap)['code']);

        $this->assertStatus(403, $this->request('POST', '/api/v1/docente/franjas', $this->slotBody(['materiaId' => 2]), $ana));
        $this->assertStatus(422, $this->request('POST', '/api/v1/docente/franjas', $this->slotBody(['horaInicio' => '17:00', 'horaFin' => '16:00']), $ana));
    }

    #[Test]
    public function no_modifica_franjas_ajenas(): void
    {
        $beto = $this->login('beto@uniminuto.edu.co');
        $this->assertStatus(403, $this->request('PATCH', '/api/v1/docente/franjas/' . Fixtures::SLOT_A . '/estado', ['activa' => false], $beto));
    }

    #[Test]
    public function franja_con_reservas_no_se_desactiva_ni_cambia_de_horario(): void
    {
        $this->book($this->login('laura@uniminuto.edu.co'), Fixtures::SLOT_A, '2027-03-08');
        $ana = $this->login('ana@uniminuto.edu.co');

        $this->assertStatus(422, $this->request('PATCH', '/api/v1/docente/franjas/' . Fixtures::SLOT_A . '/estado', ['activa' => false], $ana));
        $this->assertStatus(422, $this->request('PUT', '/api/v1/docente/franjas/' . Fixtures::SLOT_A, $this->slotBody(['diaSemana' => 1, 'horaInicio' => '09:00', 'horaFin' => '10:00']), $ana));
        // Cambiar solo el lugar sí está permitido.
        $this->assertStatus(200, $this->request('PUT', '/api/v1/docente/franjas/' . Fixtures::SLOT_A, $this->slotBody(['diaSemana' => 1, 'horaInicio' => '10:00', 'horaFin' => '11:00', 'lugar' => 'Biblioteca']), $ana));
    }
}
