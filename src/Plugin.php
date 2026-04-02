<?php

declare(strict_types=1);

namespace NextPressBuilder;

use NextPressBuilder\Admin\AdminMenu;
use NextPressBuilder\Core\AssetManager;
use NextPressBuilder\Core\Capability;
use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\HookManager;
use NextPressBuilder\Core\ModuleManager;
use NextPressBuilder\Core\RestApiManager;
use NextPressBuilder\Core\Sanitizer;
use NextPressBuilder\Core\SettingsManager;
use NextPressBuilder\Core\Validator;
use NextPressBuilder\Core\WebhookManager;
use NextPressBuilder\Core\DatabaseManager;

/**
 * Main plugin class — singleton entry point.
 *
 * Like Elementor's Plugin class: bootstraps the DI container,
 * initializes core services, discovers and loads modules.
 */
class Plugin
{
    private static ?Plugin $instance = null;

    private Container $container;
    private bool $initialized = false;

    /**
     * Get the singleton instance.
     */
    public static function instance(): self
    {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Prevent direct construction.
     */
    private function __construct()
    {
        $this->container = new Container();
    }

    /**
     * Initialize the plugin.
     * Called from the 'plugins_loaded' action.
     */
    public function init(): void
    {
        if ( $this->initialized ) {
            return;
        }

        $this->registerCoreServices();
        $this->discoverAndLoadModules();
        $this->initCoreServices();
        $this->initAdminUI();

        $this->initialized = true;

        // Fire action for extensions.
        do_action( 'npb/loaded', $this );
    }

    /**
     * Register all core services in the DI container.
     */
    private function registerCoreServices(): void
    {
        // Self-register the container.
        $this->container->instance( Container::class, $this->container );

        // Core services as singletons.
        $this->container->singleton( HookManager::class, fn() => new HookManager() );

        $this->container->singleton( Sanitizer::class, fn() => new Sanitizer() );

        $this->container->singleton( Validator::class, fn() => new Validator() );

        $this->container->singleton( SettingsManager::class, fn() => new SettingsManager() );

        $this->container->singleton( Capability::class, fn() => new Capability() );

        $this->container->singleton( DatabaseManager::class, fn( Container $c ) => new DatabaseManager(
            $c->make( SettingsManager::class )
        ) );

        $this->container->singleton( AssetManager::class, fn( Container $c ) => new AssetManager(
            $c->make( HookManager::class )
        ) );

        $this->container->singleton( WebhookManager::class, fn( Container $c ) => new WebhookManager(
            $c->make( SettingsManager::class )
        ) );

        $this->container->singleton( ModuleManager::class, fn( Container $c ) => new ModuleManager( $c ) );

        $this->container->singleton( RestApiManager::class, fn( Container $c ) => new RestApiManager(
            $c,
            $c->make( HookManager::class )
        ) );
    }

    /**
     * Discover modules, resolve dependencies, register and boot them.
     */
    private function discoverAndLoadModules(): void
    {
        /** @var ModuleManager $moduleManager */
        $moduleManager = $this->container->make( ModuleManager::class );

        // Auto-discover modules from src/Modules/*/Module.php.
        $moduleManager->discover();

        // Topological sort by dependencies.
        $moduleManager->resolve();

        // Register all modules (inject container + services).
        $moduleManager->registerAll();

        // Boot all modules (safe to use cross-module services now).
        $moduleManager->bootAll();
    }

    /**
     * Initialize core services that need WordPress hooks.
     */
    private function initCoreServices(): void
    {
        /** @var RestApiManager $restApi */
        $restApi = $this->container->make( RestApiManager::class );
        $restApi->init();

        /** @var AssetManager $assets */
        $assets = $this->container->make( AssetManager::class );
        $assets->init();

        /** @var WebhookManager $webhooks */
        $webhooks = $this->container->make( WebhookManager::class );
        /** @var HookManager $hooks */
        $hooks = $this->container->make( HookManager::class );
        $webhooks->registerHooks( $hooks );

        // Load text domain.
        $hooks->addAction( 'init', static function (): void {
            load_plugin_textdomain( 'nextpress-builder', false, dirname( NPB_PLUGIN_BASENAME ) . '/languages' );
        } );

        // Run pending migrations on every load (handles plugin updates without reactivation).
        /** @var DatabaseManager $dbManager */
        $dbManager = $this->container->make( DatabaseManager::class );
        $dbManager->runMigrations();
    }

    /**
     * Initialize admin UI (menu, pages).
     */
    private function initAdminUI(): void
    {
        if ( ! is_admin() ) {
            return;
        }

        $adminMenu = new AdminMenu();
        /** @var HookManager $hooks */
        $hooks = $this->container->make( HookManager::class );
        $hooks->addAction( 'admin_menu', [ $adminMenu, 'register' ] );
    }

    /**
     * Get the DI container.
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Get the module manager.
     */
    public function getModuleManager(): ModuleManager
    {
        return $this->container->make( ModuleManager::class );
    }

    /**
     * Resolve a service from the container (convenience method).
     */
    public function make(string $abstract): mixed
    {
        return $this->container->make( $abstract );
    }

    /**
     * Prevent cloning.
     */
    private function __clone() {}
}
