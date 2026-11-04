<?php

declare(strict_types=1);

namespace Tutorias\Booking\Application;

interface TutoringQueries
{
    /**
     * @param array{estado?: ?string, alcance?: ?string, materiaId?: ?int, desde?: ?string, hasta?: ?string, q?: ?string} $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pageSize: int}
     */
    public function search(?int $studentId, ?int $teacherId, array $filters, \DateTimeImmutable $now, int $page, int $pageSize): array;

    /** @return array<string, mixed>|null */
    public function detail(int $id): ?array;

    /** @return array<string, mixed> */
    public function studentStats(int $studentId, \DateTimeImmutable $now): array;

    /** @return array<string, mixed> */
    public function teacherStats(int $teacherId, \DateTimeImmutable $now): array;
}
