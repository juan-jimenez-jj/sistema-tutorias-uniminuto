<?php

declare(strict_types=1);

namespace Tutorias\Shared\Application;

interface TransactionManager
{
    /**
     * Ejecuta el bloque dentro de una transacción. Si el bloque lanza una excepción
     * se hace rollback y la excepción se propaga.
     *
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function transactional(callable $operation): mixed;
}
