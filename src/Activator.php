<?php

declare(strict_types=1);

namespace NextPressBuilder;

use NextPressBuilder\Core\Capability;
use NextPressBuilder\Core\DatabaseManager;
use NextPressBuilder\Core\SettingsManager;

/**
 * Plugin activation handler.
 *
 * Runs on register_activation_hook:
 * - Registers custom capabilities
 * - Sets default settings
 * - Runs database migrations (creates custom tables)
 * - Flushes rewrite rules
 */
class Activator
{
    /**
     * Run activation tasks.
     */
    public function activate(): void
    {
        $this->registerCapabilities();
        $this->setDefaultSettings();
        $this->runMigrations();
        $this->flushRewriteRules();
    }

    /**
     * Register custom capabilities and assign to roles.
     */
    private function registerCapabilities(): void
    {
        ( new Capability() )->register();
    }

    /**
     * Set default plugin settings if not already set.
     */
    private function setDefaultSettings(): void
    {
        $settings = new SettingsManager();

        $settings->setDefaults( [
            'db_version'               => '0',
            'installed_at'             => gmdate( 'Y-m-d H:i:s' ),
            'nextjs_frontend_url'      => '',
            'nextjs_revalidation_url'  => '',
            'nextjs_revalidation_secret' => wp_generate_password( 32, false ),
            'business_name'            => get_bloginfo( 'name' ),
            'business_email'           => get_option( 'admin_email' ),
            'business_phone'           => '',
            'business_address'         => '',
            'business_city'            => '',
            'business_state'           => '',
            'business_zip'             => '',
            'business_country'         => '',
            'business_latitude'        => '',
            'business_longitude'       => '',
            'rate_limit_public'        => 60,
            'rate_limit_forms'         => 5,
            'rate_limit_admin'         => 120,
        ] );
    }

    /**
     * Run database migrations to create/update custom tables.
     */
    private function runMigrations(): void
    {
        $settings = new SettingsManager();
        $dbManager = new DatabaseManager( $settings );
        $dbManager->runMigrations();
    }

    /**
     * Flush rewrite rules so REST endpoints are available.
     */
    private function flushRewriteRules(): void
    {
        flush_rewrite_rules();
    }
}
