<?php

declare(strict_types=1);

namespace NextPressBuilder\Core;

use InvalidArgumentException;

/**
 * Simple dependency injection container.
 *
 * Supports binding factories, singletons, and resolving services.
 * Like Elementor's service container but simpler and typed.
 */
class Container
{
    /** @var array<string, callable> */
    private array $bindings = [];

    /** @var array<string, callable> */
    private array $singletons = [];

    /** @var array<string, object> */
    private array $instances = [];

    /**
     * Bind a factory to the container.
     * A new instance is created each time make() is called.
     */
    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Bind a singleton to the container.
     * Only one instance is created and reused.
     */
    public function singleton(string $abstract, callable $factory): void
    {
        $this->singletons[$abstract] = $factory;
    }

    /**
     * Register an already-created instance.
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve a service from the container.
     *
     * @throws InvalidArgumentException If the service is not registered.
     */
    public function make(string $abstract): mixed
    {
        // Return existing instance (for singletons and pre-registered instances).
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Create singleton and cache it.
        if (isset($this->singletons[$abstract])) {
            $this->instances[$abstract] = ($this->singletons[$abstract])($this);
            return $this->instances[$abstract];
        }

        // Create from factory (new instance each time).
        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        throw new InvalidArgumentException(
            sprintf('Service "%s" is not registered in the container.', $abstract)
        );
    }

    /**
     * Check if a service is registered.
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract])
            || isset($this->singletons[$abstract])
            || isset($this->instances[$abstract]);
    }

    /**
     * Remove a binding from the container.
     */
    public function forget(string $abstract): void
    {
        unset(
            $this->bindings[$abstract],
            $this->singletons[$abstract],
            $this->instances[$abstract]
        );
    }
}
