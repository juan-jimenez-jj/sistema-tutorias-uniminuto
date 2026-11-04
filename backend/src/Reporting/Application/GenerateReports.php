<?php

declare(strict_types=1);

namespace Tutorias\Reporting\Application;

use Tutorias\Shared\Application\Actor;
use Tutorias\Shared\Application\Cache;
use Tutorias\Shared\Domain\Role;
use Tutorias\Shared\Domain\ValidationError;

final class GenerateReports
{
    public function __construct(private readonly ReportQueries $queries, private readonly Cache $cache)
    {
    }

    /** @return array<string, mixed> */
    public function dashboard(Actor $admin, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $admin->mustBe(Role::Administrador);
        $this->assertRange($from, $to);
        $key = 'reportes.dashboard.' . $from->format('Ymd') . '-' . $to->format('Ymd');

        // Caché corta: el panel se recalcula como máximo una vez por minuto aunque lo abran muchos coordinadores.
        return $this->cache->remember($key, 60, fn () => $this->queries->dashboard($from, $to) + [
            'periodo' => ['desde' => $from->format('Y-m-d'), 'hasta' => $to->format('Y-m-d')],
            'generadoEn' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);
    }

    /** @return iterable<array<string, mixed>> */
    public function export(Actor $admin, \DateTimeImmutable $from, \DateTimeImmutable $to): iterable
    {
        $admin->mustBe(Role::Administrador);
        $this->assertRange($from, $to);

        return $this->queries->exportRows($from, $to);
    }

    private function assertRange(\DateTimeImmutable $from, \DateTimeImmutable $to): void
    {
        if ($to < $from) {
            throw ValidationError::field('hasta', 'La fecha final debe ser posterior a la inicial.');
        }
        if ($from->diff($to)->days > 400) {
            throw ValidationError::field('hasta', 'El rango máximo de un reporte es de 400 días.');
        }
    }
}
