<?php

declare(strict_types=1);

namespace Tutorias\Catalog\Application;

use Tutorias\Catalog\Domain\Subject;
use Tutorias\Catalog\Domain\SubjectRepository;
use Tutorias\Shared\Application\Actor;
use Tutorias\Shared\Application\AuditLog;
use Tutorias\Shared\Application\Cache;
use Tutorias\Shared\Application\TransactionManager;
use Tutorias\Shared\Domain\Conflict;
use Tutorias\Shared\Domain\NotFound;
use Tutorias\Shared\Domain\Role;
use Tutorias\Shared\Domain\ValidationError;

final class ManageCatalog
{
    public const CACHE_PREFIX = 'catalogo.';

    public function __construct(
        private readonly SubjectRepository $subjects,
        private readonly CatalogQueries $queries,
        private readonly TransactionManager $tx,
        private readonly Cache $cache,
        private readonly AuditLog $audit,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function listSubjects(bool $includeInactive = false): array
    {
        // Táctica de rendimiento: el catálogo cambia poco y se consulta en cada reserva.
        return $this->cache->remember(self::CACHE_PREFIX . 'materias.' . ($includeInactive ? 'todas' : 'activas'), 300, fn () => $this->queries->subjects($includeInactive));
    }

    /** @return list<array<string, mixed>> */
    public function teachersForSubject(int $subjectId): array
    {
        $subject = $this->subjects->findById($subjectId) ?? throw NotFound::entity('materia', $subjectId);

        return $subject->isActive() ? $this->queries->teachersForSubject($subjectId) : [];
    }

    public function createSubject(Actor $admin, string $code, string $name, ?string $description, string $area): Subject
    {
        $admin->mustBe(Role::Administrador);
        $subject = Subject::create($code, $name, $description, $area);
        if ($this->subjects->codeTaken($subject->code())) {
            throw Conflict::because('subject_code_taken', "Ya existe una materia con el código {$subject->code()}.");
        }
        $this->subjects->save($subject);
        $this->cache->forgetPrefix(self::CACHE_PREFIX);
        $this->audit->record($admin->id, 'materia.creada', 'materia', $subject->id(), $subject->toArray());

        return $subject;
    }

    public function updateSubject(Actor $admin, int $id, string $code, string $name, ?string $description, string $area, bool $active): Subject
    {
        $admin->mustBe(Role::Administrador);
        $subject = $this->subjects->findById($id) ?? throw NotFound::entity('materia', $id);
        $subject->rename($code, $name, $description, $area);
        $subject->setActive($active);
        if ($this->subjects->codeTaken($subject->code(), $id)) {
            throw Conflict::because('subject_code_taken', "Ya existe una materia con el código {$subject->code()}.");
        }
        $this->subjects->save($subject);
        $this->cache->forgetPrefix(self::CACHE_PREFIX);
        $this->audit->record($admin->id, 'materia.actualizada', 'materia', $id, $subject->toArray());

        return $subject;
    }

    /** @param list<int> $subjectIds */
    public function assignSubjectsToTeacher(Actor $admin, int $teacherId, array $subjectIds): void
    {
        $admin->mustBe(Role::Administrador);
        if (!$this->subjects->isActiveTeacher($teacherId)) {
            throw NotFound::entity('docente', $teacherId);
        }
        $missing = $this->subjects->missingIds($subjectIds);
        if ($missing !== []) {
            throw ValidationError::field('materiaIds', 'Materias inexistentes: ' . implode(', ', $missing) . '.');
        }
        $this->tx->transactional(fn () => $this->subjects->replaceTeacherSubjects($teacherId, $subjectIds));
        $this->cache->forgetPrefix(self::CACHE_PREFIX);
        $this->audit->record($admin->id, 'docente.materias_asignadas', 'usuario', $teacherId, ['materias' => $subjectIds]);
    }
}
