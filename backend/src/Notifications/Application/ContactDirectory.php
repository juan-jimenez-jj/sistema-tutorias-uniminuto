<?php

declare(strict_types=1);

namespace Tutorias\Notifications\Application;

interface ContactDirectory
{
    public function find(int $userId): ?Contact;

    public function subjectName(int $subjectId): string;
}
