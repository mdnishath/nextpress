<?php

declare(strict_types=1);

namespace NextPressBuilder\Core;

use RuntimeException;

/**
 * Discovers, resolves dependencies, and manages the lifecycle of all modules.
 *
 * Like Elementor's Modules_Manager: auto-discovers modules from the Modules directory,
 * validates they implement ModuleInterface, sorts by dependencies, then registers and boots.
 */
class ModuleManager
{
    /** @var array<string, ModuleInterface> Registered modules keyed by slug. */
    private array $modules = [];

    /** @var array<string, ModuleInterface> Modules sorted by dependency order. */
    private array $sorted = [];

    /** @var bool Whether modules have been booted. */
    private bool $booted = false;

    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * Discover modules from the Modules directory.
     * Scans for Module.php files in each subdirectory.
     */
    public function discover(): void
    {
        $modulesDir = NPB_PLUGIN_DIR . 'src/Modules';

        if ( ! is_dir( $modulesDir ) ) {
            return;
        }

        $directories = glob( $modulesDir . '/*/Module.php' );

        if ( $directories === false ) {
            return;
        }

        foreach ( $directories as $moduleFile ) {
            $dirName = basename( dirname( $moduleFile ) );
            $className = 'NextPressBuilder\\Modules\\' . $dirName . '\\Module';

            if ( ! class_exists( $className ) ) {
                continue;
            }

            $module = new $className();

            if ( ! $module instanceof ModuleInterface ) {
                continue;
            }

            $this->modules[ $module->slug() ] = $module;
        }
    }

    /**
     * Add a module manually (useful for testing or programmatic registration).
     */
    public function add(ModuleInterface $module): void
    {
        $this->modules[ $module->slug() ] = $module;
    }

    /**
     * Topological sort of modules based on their declared dependencies.
     *
     * @throws RuntimeException If a circular dependency or missing dependency is detected.
     */
    public function resolve(): void
    {
        $this->sorted = [];
        $visited = [];
        $visiting = [];

        foreach ( $this->modules as $slug => $module ) {
            if ( ! isset( $visited[ $slug ] ) ) {
                $this->topologicalSort( $slug, $visited, $visiting );
            }
        }
    }

    /**
     * Recursive topological sort helper.
     *
     * @param array<string, bool> $visited
     * @param array<string, bool> $visiting
     *
     * @throws RuntimeException
     */
    private function topologicalSort(string $slug, array &$visited, array &$visiting): void
    {
        if ( isset( $visiting[ $slug ] ) ) {
            throw new RuntimeException(
                sprintf( 'Circular dependency detected for module "%s".', $slug )
            );
        }

        if ( isset( $visited[ $slug ] ) ) {
            return;
        }

        $visiting[ $slug ] = true;

        $module = $this->modules[ $slug ] ?? null;

        if ( $module === null ) {
            throw new RuntimeException(
                sprintf( 'Module "%s" is required as a dependency but is not registered.', $slug )
            );
        }

        foreach ( $module->dependencies() as $dependency ) {
            if ( ! isset( $this->modules[ $dependency ] ) ) {
                throw new RuntimeException(
                    sprintf(
                        'Module "%s" depends on "%s" which is not registered.',
                        $slug,
                        $dependency
                    )
                );
            }
            $this->topologicalSort( $dependency, $visited, $visiting );
        }

        unset( $visiting[ $slug ] );
        $visited[ $slug ] = true;
        $this->sorted[ $slug ] = $module;
    }

    /**
     * Call register() on all modules in dependency order.
     */
    public function registerAll(): void
    {
        foreach ( $this->sorted as $module ) {
            $module->register( $this->container );
        }
    }

    /**
     * Call boot() on all modules in dependency order.
     */
    public function bootAll(): void
    {
        if ( $this->booted ) {
            return;
        }

        foreach ( $this->sorted as $module ) {
            $module->boot();
        }

        $this->booted = true;
    }

    /**
     * Get a module by slug.
     *
     * @throws RuntimeException If the module is not found.
     */
    public function get(string $slug): ModuleInterface
    {
        if ( ! isset( $this->modules[ $slug ] ) ) {
            throw new RuntimeException(
                sprintf( 'Module "%s" is not registered.', $slug )
            );
        }

        return $this->modules[ $slug ];
    }

    /**
     * Check if a module is registered.
     */
    public function has(string $slug): bool
    {
        return isset( $this->modules[ $slug ] );
    }

    /**
     * Get all registered modules.
     *
     * @return array<string, ModuleInterface>
     */
    public function all(): array
    {
        return $this->modules;
    }

    /**
     * Get all sorted modules (after resolve()).
     *
     * @return array<string, ModuleInterface>
     */
    public function sorted(): array
    {
        return $this->sorted;
    }

    /**
     * Collect all REST routes from all modules.
     *
     * @return array<int, array<string, mixed>>
     */
    public function collectRoutes(): array
    {
        $routes = [];
        foreach ( $this->sorted as $module ) {
            $moduleRoutes = $module->routes();
            foreach ( $moduleRoutes as $route ) {
                $routes[] = $route;
            }
        }
        return $routes;
    }

    /**
     * Collect all migration class names from all modules.
     *
     * @return string[]
     */
    public function collectMigrations(): array
    {
        $migrations = [];
        foreach ( $this->sorted as $module ) {
            $moduleMigrations = $module->migrations();
            foreach ( $moduleMigrations as $migration ) {
                $migrations[] = $migration;
            }
        }
        return $migrations;
    }
}
