<?php

declare(strict_types=1);

namespace Tutorias\Notifications\Infrastructure;

use Tutorias\Notifications\Application\Contact;
use Tutorias\Notifications\Application\ContactDirectory;

final class PdoContactDirectory implements ContactDirectory
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function find(int $userId): ?Contact
    {
        $stmt = $this->pdo->prepare('SELECT id, nombre, email FROM usuarios WHERE id = ?');
        $stmt->execute([$userId]);
        $r = $stmt->fetch();

        return $r ? new Contact((int) $r['id'], (string) $r['nombre'], (string) $r['email']) : null;
    }

    public function subjectName(int $subjectId): string
    {
        $stmt = $this->pdo->prepare('SELECT nombre FROM materias WHERE id = ?');
        $stmt->execute([$subjectId]);

        return (string) ($stmt->fetchColumn() ?: 'Ciencias Básicas');
    }
}
