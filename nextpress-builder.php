<?php
/**
 * Plugin Name: NextPress Builder
 * Plugin URI:  https://github.com/nextpress/builder
 * Description: The WordPress page builder that outputs Next.js. Drag-and-drop visual builder for headless WordPress + Next.js websites.
 * Version:     1.0.0-alpha
 * Author:      NextPress
 * Author URI:  https://nextpress.dev
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nextpress-builder
 * Domain Path: /languages
 * Requires PHP: 8.1
 * Requires at least: 6.4
 *
 * @package NextPressBuilder
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants.
define( 'NPB_VERSION', '1.0.0-alpha' );
define( 'NPB_PLUGIN_FILE', __FILE__ );
define( 'NPB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NPB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NPB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// PHP version check.
if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
    add_action( 'admin_notices', static function (): void {
        $message = sprintf(
            /* translators: %s: minimum PHP version */
            esc_html__( 'NextPress Builder requires PHP %s or higher. Please update your PHP version.', 'nextpress-builder' ),
            '8.1'
        );
        printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
    } );
    return;
}

// WordPress version check.
if ( version_compare( get_bloginfo( 'version' ), '6.4', '<' ) ) {
    add_action( 'admin_notices', static function (): void {
        $message = sprintf(
            /* translators: %s: minimum WordPress version */
            esc_html__( 'NextPress Builder requires WordPress %s or higher. Please update WordPress.', 'nextpress-builder' ),
            '6.4'
        );
        printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
    } );
    return;
}

// Composer autoloader.
$autoloader = NPB_PLUGIN_DIR . 'vendor/autoload.php';
if ( ! file_exists( $autoloader ) ) {
    add_action( 'admin_notices', static function (): void {
        $message = esc_html__( 'NextPress Builder: Composer autoloader not found. Please run "composer install" in the plugin directory.', 'nextpress-builder' );
        printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
    } );
    return;
}
require_once $autoloader;

// Activation hook.
register_activation_hook( __FILE__, static function (): void {
    ( new \NextPressBuilder\Activator() )->activate();
} );

// Deactivation hook.
register_deactivation_hook( __FILE__, static function (): void {
    ( new \NextPressBuilder\Deactivator() )->deactivate();
} );

// Boot the plugin.
add_action( 'plugins_loaded', static function (): void {
    \NextPressBuilder\Plugin::instance()->init();
}, 10 );
