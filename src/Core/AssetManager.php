<?php

declare(strict_types=1);

namespace NextPressBuilder\Core;

/**
 * Manages admin JS/CSS asset enqueuing.
 *
 * Enqueues React admin UI scripts only on NextPress admin pages.
 * Uses @wordpress/scripts build output.
 */
class AssetManager
{
    private bool $registered = false;

    public function __construct(
        private readonly HookManager $hooks,
    ) {}

    /**
     * Initialize asset enqueuing hooks.
     */
    public function init(): void
    {
        $this->hooks->addAction( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ] );
    }

    /**
     * Enqueue admin scripts and styles on NextPress pages only.
     */
    public function enqueueAdminAssets(string $hookSuffix): void
    {
        // Only load on NextPress admin pages.
        if ( ! $this->isNextPressPage( $hookSuffix ) ) {
            return;
        }

        // Enqueue WP Media Library scripts (for image picker)
        wp_enqueue_media();

        $this->registerAdminApp();
    }

    /**
     * Register and enqueue the main React admin application.
     */
    private function registerAdminApp(): void
    {
        if ( $this->registered ) {
            return;
        }

        $assetFile = NPB_PLUGIN_DIR . 'admin/build/index.asset.php';

        if ( file_exists( $assetFile ) ) {
            $asset = require $assetFile;
        } else {
            $asset = [
                'dependencies' => [ 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ],
                'version'      => NPB_VERSION,
            ];
        }

        $scriptPath = 'admin/build/index.js';
        $stylePath  = 'admin/build/index.css';

        // Main admin script.
        if ( file_exists( NPB_PLUGIN_DIR . $scriptPath ) ) {
            wp_enqueue_script(
                'npb-admin',
                NPB_PLUGIN_URL . $scriptPath,
                $asset['dependencies'],
                $asset['version'],
                true
            );

            // Localize script with API config.
            wp_localize_script( 'npb-admin', 'npbAdmin', $this->getLocalizedData() );

            // Set script translations.
            wp_set_script_translations( 'npb-admin', 'nextpress-builder', NPB_PLUGIN_DIR . 'languages' );
        }

        // Main admin style.
        if ( file_exists( NPB_PLUGIN_DIR . $stylePath ) ) {
            wp_enqueue_style(
                'npb-admin-style',
                NPB_PLUGIN_URL . $stylePath,
                [ 'wp-components' ],
                $asset['version']
            );
        }

        $this->registered = true;
    }

    /**
     * Check if current admin page is a NextPress page.
     */
    private function isNextPressPage(string $hookSuffix): bool
    {
        return str_contains( $hookSuffix, 'nextpress' );
    }

    /**
     * Get localized data for the admin React app.
     *
     * @return array<string, mixed>
     */
    private function getLocalizedData(): array
    {
        return [
            'apiUrl'    => esc_url_raw( rest_url( 'npb/v1' ) ),
            'nonce'     => wp_create_nonce( 'wp_rest' ),
            'adminUrl'  => esc_url( admin_url() ),
            'pluginUrl' => esc_url( NPB_PLUGIN_URL ),
            'version'   => NPB_VERSION,
            'locale'    => get_locale(),
            'userId'    => get_current_user_id(),
        ];
    }

    /**
     * Enqueue a custom admin script for a specific page.
     */
    public function enqueueScript(
        string $handle,
        string $src,
        array $deps = [],
        ?string $version = null,
    ): void {
        wp_enqueue_script(
            "npb-{$handle}",
            NPB_PLUGIN_URL . $src,
            $deps,
            $version ?? NPB_VERSION,
            true
        );
    }

    /**
     * Enqueue a custom admin style for a specific page.
     */
    public function enqueueStyle(
        string $handle,
        string $src,
        array $deps = [],
        ?string $version = null,
    ): void {
        wp_enqueue_style(
            "npb-{$handle}",
            NPB_PLUGIN_URL . $src,
            $deps,
            $version ?? NPB_VERSION
        );
    }
}
