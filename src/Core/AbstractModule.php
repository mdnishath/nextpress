<?php

declare(strict_types=1);

namespace NextPressBuilder\Core;

/**
 * Base module class providing common functionality.
 *
 * All modules should extend this class instead of implementing
 * ModuleInterface directly. Provides sensible defaults.
 */
abstract class AbstractModule implements ModuleInterface
{
    protected Container $container;

    /**
     * Default: no dependencies.
     *
     * @return string[]
     */
    public function dependencies(): array
    {
        return [];
    }

    /**
     * Default: no REST routes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function routes(): array
    {
        return [];
    }

    /**
     * Default: no migrations.
     *
     * @return string[]
     */
    public function migrations(): array
    {
        return [];
    }

    /**
     * Store the container reference during registration.
     */
    public function register(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * Default: nothing to do on boot.
     */
    public function boot(): void
    {
        // Override in concrete modules if needed.
    }

    /**
     * Get a service from the container.
     */
    protected function resolve(string $abstract): mixed
    {
        return $this->container->make($abstract);
    }
}
