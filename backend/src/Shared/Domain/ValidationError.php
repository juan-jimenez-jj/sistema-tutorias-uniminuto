<?php

declare(strict_types=1);

namespace Tutorias\Shared\Domain;

final class ValidationError extends DomainError
{
    /** @param array<string, string> $fieldErrors */
    public function __construct(private readonly array $fieldErrors, string $message = 'Los datos enviados no son válidos.')
    {
        parent::__construct($message, 'validation_failed', ['fields' => $fieldErrors]);
    }

    public static function field(string $field, string $message): self
    {
        return new self([$field => $message], $message);
    }

    /** @return array<string, string> */
    public function fieldErrors(): array
    {
        return $this->fieldErrors;
    }
}
