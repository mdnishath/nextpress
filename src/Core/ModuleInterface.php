<?php

declare(strict_types=1);

namespace NextPressBuilder\Core;

/**
 * Contract that all NextPress Builder modules must implement.
 *
 * Each module is a self-contained unit (like an Elementor module)
 * with its own controllers, services, REST routes, and migrations.
 */
interface ModuleInterface
{
    /**
     * Unique machine-readable identifier (e.g., 'page-builder', 'theme-manager').
     */
    public function slug(): string;

    /**
     * Human-readable display name.
     */
    public function name(): string;

    /**
     * Semantic version of this module.
     */
    public function version(): string;

    /**
     * Array of module slugs this module depends on.
     * ModuleManager will ensure dependencies are loaded first.
     *
     * @return string[]
     */
    public function dependencies(): array;

    /**
     * Register services, repositories, and hooks with the container.
     * Called during the registration phase (before boot).
     */
    public function register(Container $container): void;

    /**
     * Called after ALL modules have been registered.
     * Safe to use services from other modules here.
     */
    public function boot(): void;

    /**
     * Return REST API route definitions for this module.
     *
     * Each route: ['method' => 'GET|POST|PUT|DELETE', 'path' => '/resource', 'callback' => callable, 'permission' => callable]
     *
     * @return array<int, array<string, mixed>>
     */
    public function routes(): array;

    /**
     * Return database migration class names for this module.
     *
     * @return string[]
     */
    public function migrations(): array;
}
