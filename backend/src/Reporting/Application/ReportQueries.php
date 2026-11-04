<?php

declare(strict_types=1);

namespace Tutorias\Reporting\Application;

interface ReportQueries
{
    /** @return array<string, mixed> */
    public function dashboard(\DateTimeImmutable $from, \DateTimeImmutable $to): array;

    /** @return iterable<array<string, mixed>> filas para exportar (streaming) */
    public function exportRows(\DateTimeImmutable $from, \DateTimeImmutable $to): iterable;

    /** @return array{items: list<array<string, mixed>>, total: int, page: int, pageSize: int} */
    public function auditTrail(?string $action, int $page, int $pageSize): array;
}
