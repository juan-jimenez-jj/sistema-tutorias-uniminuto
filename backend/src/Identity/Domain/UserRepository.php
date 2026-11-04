<?php

declare(strict_types=1);

namespace Tutorias\Identity\Domain;

interface UserRepository
{
    public function findById(int $id): ?User;

    public function findByEmail(InstitutionalEmail $email): ?User;

    public function emailTaken(InstitutionalEmail $email, ?int $exceptUserId = null): bool;

    public function institutionalIdTaken(string $institutionalId, ?int $exceptUserId = null): bool;

    public function save(User $user): void;
}
