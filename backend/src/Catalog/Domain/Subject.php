<?php

declare(strict_types=1);

namespace Tutorias\Catalog\Domain;

use Tutorias\Shared\Domain\ValidationError;

/** Materia del área de Ciencias Básicas. */
final class Subject
{
    public const AREAS = ['Matemáticas', 'Física', 'Química', 'Estadística'];

    private function __construct(
        private ?int $id,
        private string $code,
        private string $name,
        private ?string $description,
        private string $area,
        private bool $active,
    ) {
    }

    public static function create(string $code, string $name, ?string $description, string $area): self
    {
        $subject = new self(null, '', '', null, 'Matemáticas', true);
        $subject->rename($code, $name, $description, $area);

        return $subject;
    }

    public static function fromPersistence(int $id, string $code, string $name, ?string $description, string $area, bool $active): self
    {
        return new self($id, $code, $name, $description, $area, $active);
    }

    public function rename(string $code, string $name, ?string $description, string $area): void
    {
        $code = strtoupper(trim($code));
        if (!preg_match('/^[A-Z]{3}\d{3}$/', $code)) {
            throw ValidationError::field('codigo', 'El código debe tener 3 letras y 3 números, p. ej. MAT101.');
        }
        if (!in_array($area, self::AREAS, true)) {
            throw ValidationError::field('area', 'Área no válida. Usa: ' . implode(', ', self::AREAS) . '.');
        }
        $this->code = $code;
        $this->name = trim($name);
        $this->description = $description;
        $this->area = $area;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function assignId(int $id): void
    {
        $this->id = $id;
    }

    public function id(): ?int
    { return $this->id; }
    public function code(): string
    { return $this->code; }
    public function name(): string
    { return $this->name; }
    public function description(): ?string
    { return $this->description; }
    public function area(): string
    { return $this->area; }
    public function isActive(): bool
    { return $this->active; }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->code,
            'nombre' => $this->name,
            'descripcion' => $this->description,
            'area' => $this->area,
            'estado' => $this->active ? 'activa' : 'inactiva',
        ];
    }
}
