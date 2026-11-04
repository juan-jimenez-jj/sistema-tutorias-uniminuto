<?php

declare(strict_types=1);

namespace Tutorias\Tests\Unit\Shared;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tutorias\Shared\Application\Actor;
use Tutorias\Shared\Domain\Role;
use Tutorias\Shared\Domain\Unauthorized;
use Tutorias\Shared\Infrastructure\Security\TokenService;
use Tutorias\Tests\Support\FrozenClock;

final class TokenServiceTest extends TestCase
{
    private const SECRET = 'un-secreto-de-pruebas-suficientemente-largo-123';

    #[Test]
    public function emite_y_verifica_un_token(): void
    {
        $clock = new FrozenClock('2027-03-01 08:00');
        $service = new TokenService(self::SECRET, $clock, 3600);
        $actor = $service->verify($service->issue(new Actor(7, Role::Docente, 'Ana', 'ana@uniminuto.edu.co')));

        self::assertSame(7, $actor->id);
        self::assertSame(Role::Docente, $actor->role);
    }

    #[Test]
    public function rechaza_token_expirado(): void
    {
        $clock = new FrozenClock('2027-03-01 08:00');
        $service = new TokenService(self::SECRET, $clock, 3600);
        $token = $service->issue(new Actor(7, Role::Docente, 'Ana', 'ana@uniminuto.edu.co'));
        $clock->travelTo('2027-03-01 09:01');

        $this->expectException(Unauthorized::class);
        $service->verify($token);
    }

    #[Test]
    public function rechaza_token_alterado_para_escalar_privilegios(): void
    {
        $service = new TokenService(self::SECRET, new FrozenClock(), 3600);
        [$header, $payload, $signature] = explode('.', $service->issue(new Actor(7, Role::Estudiante, 'Laura', 'l@uniminuto.edu.co')));
        $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        $claims['rol'] = 'administrador';
        $forged = rtrim(strtr(base64_encode((string) json_encode($claims)), '+/', '-_'), '=');

        $this->expectException(Unauthorized::class);
        $service->verify("{$header}.{$forged}.{$signature}");
    }

    #[Test]
    public function exige_un_secreto_robusto(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TokenService('corto', new FrozenClock());
    }
}
