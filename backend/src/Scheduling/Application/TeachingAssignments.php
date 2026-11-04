<?php

declare(strict_types=1);

namespace Tutorias\Scheduling\Application;

/** Puerto hacia el Catálogo: ¿el docente tiene asignada la materia? */
interface TeachingAssignments
{
    public function teaches(int $teacherId, int $subjectId): bool;
}
