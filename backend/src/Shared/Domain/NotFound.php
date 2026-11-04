<?php

declare(strict_types=1);

namespace Tutorias\Shared\Domain;

final class NotFound extends DomainError
{
    public static function entity(string $entity, int|string $id): self
    {
        return new self("No se encontró {$entity} con identificador {$id}.", 'not_found', ['entity' => $entity, 'id' => $id]);
    }
}
