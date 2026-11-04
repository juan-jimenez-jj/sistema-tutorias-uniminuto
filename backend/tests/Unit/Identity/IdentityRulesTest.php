<?php

declare(strict_types=1);

namespace Tutorias\Tests\Unit\Identity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tutorias\Identity\Domain\InstitutionalEmail;
use Tutorias\Identity\Domain\PasswordPolicy;
use Tutorias\Identity\Domain\User;
use Tutorias\Shared\Domain\BusinessRuleViolation;
use Tutorias\Shared\Domain\Role;
use Tutorias\Shared\Domain\ValidationError;

final class IdentityRulesTest extends TestCase
{
    #[Test]
    public function normaliza_y_acepta_correo_institucional(): void
    {
        self::assertSame('laura.gomez@uniminuto.edu.co', InstitutionalEmail::fromString('  Laura.Gomez@UNIMINUTO.edu.co ')->value);
    }

    /** @return iterable<array{string}> */
    public static function correosInvalidos(): iterable
    {
        yield ['laura@gmail.com'];
        yield ['laura@uniminuto.edu.co.evil.com'];
        yield ["' OR '1'='1"];
        yield ['sin-arroba'];
    }

    #[Test]
    #[DataProvider('correosInvalidos')]
    public function rechaza_correos_no_institucionales(string $email): void
    {
        $this->expectException(ValidationError::class);
        InstitutionalEmail::fromString($email);
    }

    /** @return iterable<array{string}> */
    public static function contrasenasDebiles(): iterable
    {
        yield 'corta' => ['abc123'];
        yield 'solo letras' => ['contrasena'];
        yield 'solo números' => ['12345678'];
        yield 'más de 72 bytes' => [str_repeat('a1', 40)];
    }

    #[Test]
    #[DataProvider('contrasenasDebiles')]
    public function politica_de_contrasenas(string $password): void
    {
        $this->expectException(ValidationError::class);
        PasswordPolicy::assertValid($password);
    }

    #[Test]
    public function acepta_contrasena_valida(): void
    {
        PasswordPolicy::assertValid('Tutorias2027');
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function el_registro_publico_solo_crea_estudiantes_con_consentimiento(): void
    {
        $now = new \DateTimeImmutable('2027-03-01');
        $user = User::registerStudent('est-001', 'Laura', InstitutionalEmail::fromString('laura@uniminuto.edu.co'), 'hash', 'Sistemas', $now);
        self::assertSame(Role::Estudiante, $user->role());
        self::assertSame('EST-001', $user->institutionalId());
        self::assertEquals($now, $user->dataConsentAt());
    }

    #[Test]
    public function un_administrador_no_puede_desactivarse_a_si_mismo(): void
    {
        $admin = User::fromPersistence(1, 'ADM', 'Admin', 'admin@uniminuto.edu.co', 'h', Role::Administrador, null, \Tutorias\Identity\Domain\UserStatus::Activo, null, null, new \DateTimeImmutable());
        $this->expectException(BusinessRuleViolation::class);
        $admin->deactivate(1);
    }
}
