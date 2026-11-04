<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Contenedor PSR-11 mínimo con fábricas perezosas y singletons.
 * El cableado explícito (ver config/container.php) es la "raíz de composición":
 * el único lugar del sistema que conoce las implementaciones concretas.
 */
final class Container implements ContainerInterface
{
    /** @var array<string, callable(self): mixed> */
    private array $factories = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    public function instance(string $id, mixed $value): void
    {
        $this->instances[$id] = $value;
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }
        if (isset($this->factories[$id])) {
            return $this->instances[$id] = ($this->factories[$id])($this);
        }
        if (class_exists($id)) {
            return $this->instances[$id] = $this->autowire($id);
        }

        throw new class ("Servicio no registrado: {$id}") extends \RuntimeException implements NotFoundExceptionInterface {
        };
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]) || array_key_exists($id, $this->instances) || class_exists($id);
    }

    /**
     * Autowiring por tipos del constructor: las clases concretas (controladores, casos de uso)
     * no necesitan registrarse; las interfaces sí, en config/container.php.
     */
    private function autowire(string $class): object
    {
        $reflection = new \ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new \RuntimeException("No se puede instanciar {$class}: registra una implementación en config/container.php.");
        }
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $reflection->newInstance();
        }
        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->get($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new \RuntimeException("No se puede resolver el parámetro \${$param->getName()} de {$class}.");
            }
        }

        return $reflection->newInstanceArgs($args);
    }
}
