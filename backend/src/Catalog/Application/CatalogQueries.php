<?php

declare(strict_types=1);

namespace Tutorias\Catalog\Application;

interface CatalogQueries
{
    /** @return list<array<string, mixed>> */
    public function subjects(bool $includeInactive): array;

    /** @return list<array<string, mixed>> docentes activos que dictan la materia y tienen franjas activas */
    public function teachersForSubject(int $subjectId): array;
}
