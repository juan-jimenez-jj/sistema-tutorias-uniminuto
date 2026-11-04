<?php

declare(strict_types=1);

namespace Tutorias\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use Tutorias\Tests\Support\ApiTestCase;
use Tutorias\Tests\Support\Fixtures;

final class AuthApiTest extends ApiTestCase
{
    #[Test]
    public function registro_de_estudiante_con_correo_institucional(): void
    {
        $response = $this->request('POST', '/api/v1/auth/registro', [
            'idInstitucional' => '000123456', 'nombre' => 'Nueva Estudiante', 'email' => 'nueva@uniminuto.edu.co',
            'password' => 'Segura2027', 'programaAcademico' => 'Psicología', 'aceptaTratamientoDatos' => true,
        ]);
        $this->assertStatus(201, $response);
        self::assertSame('estudiante', $this->json($response)['usuario']['rol']);
        self::assertArrayNotHasKey('password_hash', $this->json($response)['usuario']);

        $this->login('nueva@uniminuto.edu.co', 'Segura2027');
    }

    #[Test]
    public function registro_rechaza_correo_externo_duplicados_y_falta_de_consentimiento(): void
    {
        $base = ['idInstitucional' => '000999', 'nombre' => 'X Y Z', 'password' => 'Segura2027', 'programaAcademico' => 'Psicología', 'aceptaTratamientoDatos' => true];

        $this->assertStatus(422, $this->request('POST', '/api/v1/auth/registro', $base + ['email' => 'x@gmail.com']));
        $this->assertStatus(409, $this->request('POST', '/api/v1/auth/registro', $base + ['email' => 'laura@uniminuto.edu.co']));
        $this->assertStatus(422, $this->request('POST', '/api/v1/auth/registro', ['aceptaTratamientoDatos' => false, 'email' => 'otra@uniminuto.edu.co'] + $base));
    }

    #[Test]
    public function login_verifica_la_contrasena_corrige_vulnerabilidad_heredada(): void
    {
        // En el sistema heredado bastaba con un correo existente: la contraseña nunca se verificaba.
        $this->assertStatus(401, $this->request('POST', '/api/v1/auth/login', ['email' => 'admin@uniminuto.edu.co', 'password' => 'cualquier-cosa']));
    }

    #[Test]
    public function login_resiste_inyeccion_sql(): void
    {
        foreach (["' OR '1'='1", "admin@uniminuto.edu.co' -- ", "' UNION SELECT 1,2,3,4,5,6,7 -- "] as $payload) {
            $this->assertStatus(401, $this->request('POST', '/api/v1/auth/login', ['email' => $payload, 'password' => 'x']));
        }
    }

    #[Test]
    public function bloquea_tras_cinco_intentos_fallidos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->assertStatus(401, $this->request('POST', '/api/v1/auth/login', ['email' => 'laura@uniminuto.edu.co', 'password' => 'mala' . $i]));
        }
        $blocked = $this->request('POST', '/api/v1/auth/login', ['email' => 'laura@uniminuto.edu.co', 'password' => Fixtures::PASSWORD]);
        $this->assertStatus(429, $blocked);
        self::assertNotEmpty($blocked->getHeaderLine('Retry-After'));

        $this->clock->travelTo('2027-03-01 08:16:00');
        $this->assertStatus(200, $this->request('POST', '/api/v1/auth/login', ['email' => 'laura@uniminuto.edu.co', 'password' => Fixtures::PASSWORD]));
    }

    #[Test]
    public function cuenta_inactiva_no_puede_iniciar_sesion(): void
    {
        $this->pdo->exec("UPDATE usuarios SET estado = 'inactivo' WHERE id = " . Fixtures::STUDENT_SARA);
        $this->assertStatus(401, $this->request('POST', '/api/v1/auth/login', ['email' => 'sara@uniminuto.edu.co', 'password' => Fixtures::PASSWORD]));
    }

    #[Test]
    public function login_entrega_cookie_httponly_y_cookie_csrf(): void
    {
        $response = $this->request('POST', '/api/v1/auth/login', ['email' => 'laura@uniminuto.edu.co', 'password' => Fixtures::PASSWORD]);
        $cookies = implode("\n", $response->getHeader('Set-Cookie'));
        self::assertMatchesRegularExpression('/tut_session=[^;]+;.*HttpOnly/', $cookies);
        self::assertStringContainsString('SameSite=Strict', $cookies);
        self::assertMatchesRegularExpression('/tut_csrf=[a-f0-9]{64}/', $cookies);
    }

    #[Test]
    public function con_cookie_las_escrituras_exigen_token_csrf(): void
    {
        $response = $this->request('POST', '/api/v1/auth/login', ['email' => 'laura@uniminuto.edu.co', 'password' => Fixtures::PASSWORD]);
        preg_match('/tut_session=([^;]+)/', implode("\n", $response->getHeader('Set-Cookie')), $s);
        preg_match('/tut_csrf=([^;]+)/', implode("\n", $response->getHeader('Set-Cookie')), $c);
        $cookie = "tut_session={$s[1]}; tut_csrf={$c[1]}";
        $body = ['franjaId' => Fixtures::SLOT_A, 'fecha' => '2027-03-08', 'tema' => 'Límites'];

        $this->assertStatus(200, $this->request('GET', '/api/v1/auth/yo', null, null, ['Cookie' => $cookie]));
        $this->assertStatus(403, $this->request('POST', '/api/v1/tutorias', $body, null, ['Cookie' => $cookie]));
        $this->assertStatus(403, $this->request('POST', '/api/v1/tutorias', $body, null, ['Cookie' => $cookie, 'X-CSRF-Token' => 'falso']));
        $this->assertStatus(201, $this->request('POST', '/api/v1/tutorias', $body, null, ['Cookie' => $cookie, 'X-CSRF-Token' => urldecode($c[1])]));
    }

    #[Test]
    public function rutas_protegidas_exigen_autenticacion_y_rol(): void
    {
        $this->assertStatus(401, $this->request('GET', '/api/v1/auth/yo'));
        $this->assertStatus(401, $this->request('GET', '/api/v1/tutorias', null, 'token.invalido.x'));

        $student = $this->login('laura@uniminuto.edu.co');
        $this->assertStatus(403, $this->request('GET', '/api/v1/admin/usuarios', null, $student));
        $this->assertStatus(403, $this->request('GET', '/api/v1/docente/franjas', null, $student));
        $this->assertStatus(403, $this->request('GET', '/api/v1/admin/reportes', null, $this->login('ana@uniminuto.edu.co')));
    }

    #[Test]
    public function respuestas_incluyen_cabeceras_de_seguridad(): void
    {
        $response = $this->request('GET', '/health');
        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        self::assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        self::assertNotEmpty($response->getHeaderLine('X-Request-Id'));
    }

    #[Test]
    public function cambio_de_contrasena(): void
    {
        $token = $this->login('laura@uniminuto.edu.co');
        $this->assertStatus(422, $this->request('PUT', '/api/v1/auth/password', ['passwordActual' => 'mala', 'passwordNueva' => 'Nueva2027x'], $token));
        $this->assertStatus(204, $this->request('PUT', '/api/v1/auth/password', ['passwordActual' => Fixtures::PASSWORD, 'passwordNueva' => 'Nueva2027x'], $token));
        $this->login('laura@uniminuto.edu.co', 'Nueva2027x');
    }
}
