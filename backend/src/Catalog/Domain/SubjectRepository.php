<?php

declare(strict_types=1);

namespace Tutorias\Catalog\Domain;

interface SubjectRepository
{
    public function findById(int $id): ?Subject;

    public function codeTaken(string $code, ?int $exceptId = null): bool;

    public function save(Subject $subject): void;

    /** @param list<int> $subjectIds */
    public function replaceTeacherSubjects(int $teacherId, array $subjectIds): void;

    public function teacherTeaches(int $teacherId, int $subjectId): bool;

    public function isActiveTeacher(int $userId): bool;

    /** @param list<int> $ids @return list<int> ids que no existen */
    public function missingIds(array $ids): array;
}
