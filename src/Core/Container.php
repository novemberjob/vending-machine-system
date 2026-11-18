<?php
declare(strict_types=1);

namespace VMS\Core;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Minimal dependency-injection container with constructor autowiring.
 */
final class Container
{
    /** @var array<string, callable(self): mixed> */
    private array $definitions = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /** Register a factory. Prefix the id with '$' to bind a scalar parameter by name. */
    public function set(string $id, callable $factory): void
    {
        $this->definitions[$id] = $factory;
        unset($this->instances[$id]);
    }

    /** Register an already-created shared instance. */
    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
        unset($this->definitions[$id]);
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id]) || isset($this->instances[$id]);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->definitions[$id])) {
            $object = ($this->definitions[$id])($this);
        } else {
            $object = $this->autowire($id);
        }

        $this->instances[$id] = $object;
        return $object;
    }

    private function autowire(string $id): object
    {
        if (!class_exists($id)) {
            throw new RuntimeException("Cannot resolve [{$id}] in container.");
        }

        $reflection = new ReflectionClass($id);
        $constructor = $reflection->getConstructor();
        $args = [];

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->get($type->getName());
                continue;
            }

            $scalarId = '$' . $parameter->getName();
            if (isset($this->definitions[$scalarId])) {
                $args[] = ($this->definitions[$scalarId])($this);
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException("Cannot resolve parameter \${$parameter->getName()} of [{$id}].");
        }

        return $reflection->newInstanceArgs($args);
    }
}
