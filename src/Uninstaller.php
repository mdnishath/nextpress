<?php

declare(strict_types=1);

namespace NextPressBuilder;

use NextPressBuilder\Core\Capability;

/**
 * Plugin uninstall handler.
 *
 * Called when the plugin is deleted from WordPress.
 * Removes ALL plugin data: custom tables, wp_options, capabilities.
 */
class Uninstaller
{
    /**
     * Run uninstall cleanup.
     */
    public function uninstall(): void
    {
        $this->dropCustomTables();
        $this->removeOptions();
        $this->removeCapabilities();
        $this->removeTransients();
    }

    /**
     * Drop all custom database tables.
     */
    private function dropCustomTables(): void
    {
        global $wpdb;

        $tables = [
            'npb_form_submissions',
            'npb_sections',
            'npb_style_variants',
            'npb_pages',
            'npb_components',
            'npb_forms',
            'npb_themes',
            'npb_buttons',
            'npb_navigation_menus',
            'npb_templates',
        ];

        foreach ( $tables as $table ) {
            $fullTable = $wpdb->prefix . $table;
            $wpdb->query( "DROP TABLE IF EXISTS {$fullTable}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }
    }

    /**
     * Remove all wp_options entries with the npb_ prefix.
     */
    private function removeOptions(): void
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                'npb_%'
            )
        );
    }

    /**
     * Remove custom capabilities from all roles.
     */
    private function removeCapabilities(): void
    {
        ( new Capability() )->remove();
    }

    /**
     * Remove transients.
     */
    private function removeTransients(): void
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_npb_%',
                '_transient_timeout_npb_%'
            )
        );
    }
}
