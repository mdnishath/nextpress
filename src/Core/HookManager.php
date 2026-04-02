<?php

declare(strict_types=1);

namespace NextPressBuilder\Core;

/**
 * Centralized WordPress hook registration.
 *
 * Provides a clean API for registering actions and filters,
 * with module-scoped namespacing support.
 */
class HookManager
{
    /**
     * Register a WordPress action hook.
     */
    public function addAction(
        string $hook,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1,
    ): void {
        add_action( $hook, $callback, $priority, $acceptedArgs );
    }

    /**
     * Register a WordPress filter hook.
     */
    public function addFilter(
        string $hook,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1,
    ): void {
        add_filter( $hook, $callback, $priority, $acceptedArgs );
    }

    /**
     * Fire a WordPress action hook.
     */
    public function doAction(string $hook, mixed ...$args): void
    {
        do_action( $hook, ...$args );
    }

    /**
     * Apply a WordPress filter.
     */
    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        return apply_filters( $hook, $value, ...$args );
    }

    /**
     * Remove a WordPress action hook.
     */
    public function removeAction(string $hook, callable $callback, int $priority = 10): void
    {
        remove_action( $hook, $callback, $priority );
    }

    /**
     * Remove a WordPress filter hook.
     */
    public function removeFilter(string $hook, callable $callback, int $priority = 10): void
    {
        remove_filter( $hook, $callback, $priority );
    }

    /**
     * Register a module-scoped action (prefixed with npb/).
     */
    public function moduleAction(string $moduleSlug, string $action, callable $callback, int $priority = 10): void
    {
        $this->addAction( "npb/{$moduleSlug}/{$action}", $callback, $priority );
    }

    /**
     * Fire a module-scoped action.
     */
    public function doModuleAction(string $moduleSlug, string $action, mixed ...$args): void
    {
        $this->doAction( "npb/{$moduleSlug}/{$action}", ...$args );
    }

    /**
     * Register a module-scoped filter (prefixed with npb/).
     */
    public function moduleFilter(string $moduleSlug, string $filter, callable $callback, int $priority = 10): void
    {
        $this->addFilter( "npb/{$moduleSlug}/{$filter}", $callback, $priority );
    }

    /**
     * Apply a module-scoped filter.
     */
    public function applyModuleFilter(string $moduleSlug, string $filter, mixed $value, mixed ...$args): mixed
    {
        return $this->applyFilters( "npb/{$moduleSlug}/{$filter}", $value, ...$args );
    }
}
