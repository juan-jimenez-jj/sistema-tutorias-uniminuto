<?php

declare(strict_types=1);

namespace Tutorias\Identity\Application;

/** Puerto de lectura (CQRS ligero): listados paginados sin pasar por el agregado. */
interface UserQueries
{
    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pageSize: int}
     */
    public function search(?string $term, ?string $role, ?string $status, int $page, int $pageSize): array;
}
