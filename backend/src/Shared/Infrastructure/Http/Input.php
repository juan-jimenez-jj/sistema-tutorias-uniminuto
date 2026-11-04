<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Http;

use Psr\Http\Message\ServerRequestInterface as Request;
use Tutorias\Shared\Domain\ValidationError;

/**
 * Lector y validador de la entrada HTTP. Acumula todos los errores de campo y los
 * lanza juntos (validación en el borde del sistema; el dominio vuelve a validar sus invariantes).
 */
final class Input
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @param array<string, mixed> $data */
    private function __construct(private readonly array $data)
    {
    }

    public static function body(Request $request): self
    {
        $parsed = $request->getParsedBody();

        return new self(is_array($parsed) ? $parsed : []);
    }

    public static function query(Request $request): self
    {
        return new self($request->getQueryParams());
    }

    public function requiredString(string $field, int $min = 1, int $max = 255, string $label = ''): string
    {
        $value = $this->data[$field] ?? null;
        $label = $label ?: $field;
        if (!is_string($value) || trim($value) === '') {
            $this->errors[$field] = "El campo {$label} es obligatorio.";

            return '';
        }
        $value = trim($value);
        $len = mb_strlen($value);
        if ($len < $min || $len > $max) {
            $this->errors[$field] = "El campo {$label} debe tener entre {$min} y {$max} caracteres.";
        }

        return $value;
    }

    public function optionalString(string $field, int $max = 1000): ?string
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }
        if (!is_string($value)) {
            $this->errors[$field] = "El campo {$field} debe ser texto.";

            return null;
        }
        if (mb_strlen(trim($value)) > $max) {
            $this->errors[$field] = "El campo {$field} admite máximo {$max} caracteres.";
        }

        return trim($value);
    }

    public function password(string $field): string
    {
        $value = $this->data[$field] ?? null;
        if (!is_string($value) || $value === '') {
            $this->errors[$field] = 'La contraseña es obligatoria.';

            return '';
        }

        return $value; // nunca se recorta ni se transforma una contraseña
    }

    public function requiredInt(string $field, int $min = 1, ?int $max = null): int
    {
        $value = $this->data[$field] ?? null;
        if (!is_numeric($value) || (int) $value != $value) {
            $this->errors[$field] = "El campo {$field} debe ser un número entero.";

            return 0;
        }
        $int = (int) $value;
        if ($int < $min || ($max !== null && $int > $max)) {
            $this->errors[$field] = $max === null
                ? "El campo {$field} debe ser mayor o igual a {$min}."
                : "El campo {$field} debe estar entre {$min} y {$max}.";
        }

        return $int;
    }

    public function optionalInt(string $field): ?int
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        return $this->requiredInt($field, PHP_INT_MIN);
    }

    public function requiredDate(string $field): \DateTimeImmutable
    {
        $value = $this->data[$field] ?? null;
        $date = is_string($value) ? \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('America/Bogota')) : false;
        if ($date === false || $date->format('Y-m-d') !== $value) {
            $this->errors[$field] = "El campo {$field} debe ser una fecha con formato AAAA-MM-DD.";

            return new \DateTimeImmutable('today');
        }

        return $date;
    }

    public function optionalDate(string $field): ?\DateTimeImmutable
    {
        if (!isset($this->data[$field]) || $this->data[$field] === '') {
            return null;
        }

        return $this->requiredDate($field);
    }

    public function requiredTime(string $field): string
    {
        $value = $this->data[$field] ?? null;
        if (!is_string($value) || !preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:00)?$/', $value)) {
            $this->errors[$field] = "El campo {$field} debe ser una hora con formato HH:MM.";

            return '00:00';
        }

        return substr($value, 0, 5);
    }

    /**
     * @template T of \BackedEnum
     * @param class-string<T> $enum
     * @return T|null
     */
    public function enum(string $field, string $enum, bool $required = true): ?\BackedEnum
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || $value === '') {
            if ($required) {
                $this->errors[$field] = "El campo {$field} es obligatorio.";
            }

            return null;
        }
        $intBacked = (string) (new \ReflectionEnum($enum))->getBackingType() === 'int';
        if ($intBacked) {
            $case = is_numeric($value) && (int) $value == $value ? $enum::tryFrom((int) $value) : null;
        } else {
            $case = is_string($value) ? $enum::tryFrom($value) : null;
        }
        if ($case === null) {
            $allowed = implode(', ', array_map(static fn ($c) => (string) $c->value, $enum::cases()));
            $this->errors[$field] = "Valor inválido para {$field}. Permitidos: {$allowed}.";
        }

        return $case;
    }

    public function bool(string $field, bool $default = false): bool
    {
        $value = $this->data[$field] ?? $default;

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /** @return list<int> */
    public function intList(string $field): array
    {
        $value = $this->data[$field] ?? [];
        if (!is_array($value)) {
            $this->errors[$field] = "El campo {$field} debe ser una lista.";

            return [];
        }
        $result = [];
        foreach ($value as $item) {
            if (!is_numeric($item)) {
                $this->errors[$field] = "El campo {$field} solo admite números.";

                return [];
            }
            $result[] = (int) $item;
        }

        return array_values(array_unique($result));
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    /** Lanza ValidationError si se acumuló algún error. */
    public function validate(): void
    {
        if ($this->errors !== []) {
            throw new ValidationError($this->errors);
        }
    }
}
