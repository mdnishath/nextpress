<?php

declare(strict_types=1);

namespace NextPressBuilder\Core;

/**
 * Manages database schema: runs migrations, tracks version, handles uninstall cleanup.
 *
 * Uses WordPress dbDelta() for table creation/updates and tracks schema version
 * in wp_options so migrations are only run once per version.
 */
class DatabaseManager
{
    private string $charsetCollate;
    private string $prefix;
    private SettingsManager $settings;

    public function __construct(SettingsManager $settings)
    {
        global $wpdb;

        $this->settings = $settings;
        $this->prefix = $wpdb->prefix . 'npb_';
        $this->charsetCollate = $wpdb->get_charset_collate();
    }

    /**
     * Run all pending migrations in order.
     */
    public function runMigrations(): void
    {
        $currentVersion = $this->getVersion();
        $migrations = $this->getMigrations();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ($migrations as $migration) {
            if (version_compare($migration->version(), $currentVersion, '>')) {
                $migration->up();
                $this->setVersion($migration->version());
            }
        }
    }

    /**
     * Get all migration instances sorted by version.
     *
     * @return Migration\MigrationInterface[]
     */
    private function getMigrations(): array
    {
        $migrationDir = NPB_PLUGIN_DIR . 'src/Core/Migration/migrations/';

        if (!is_dir($migrationDir)) {
            return [];
        }

        $files = glob($migrationDir . '*.php');
        if ($files === false) {
            return [];
        }

        $migrations = [];
        foreach ($files as $file) {
            require_once $file;

            // Convert filename to class name: 001_CreatePagesTable.php -> CreatePagesTable
            $basename = basename($file, '.php');
            $parts = explode('_', $basename, 2);
            $className = 'NextPressBuilder\\Core\\Migration\\migrations\\' . ($parts[1] ?? $basename);

            if (class_exists($className)) {
                $migrations[] = new $className();
            }
        }

        usort($migrations, fn($a, $b) => version_compare($a->version(), $b->version()));

        return $migrations;
    }

    /**
     * Get the current schema version.
     */
    public function getVersion(): string
    {
        return $this->settings->getString('db_version', '0');
    }

    /**
     * Set the current schema version.
     */
    public function setVersion(string $version): void
    {
        $this->settings->set('db_version', $version);
    }

    /**
     * Check if a table exists.
     */
    public function tableExists(string $table): bool
    {
        global $wpdb;

        $fullTable = $this->prefix . $table;
        $result = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $fullTable)
        );

        return $result === $fullTable;
    }

    /**
     * Drop all NextPress custom tables. Used by Uninstaller only.
     */
    public function dropAllTables(): void
    {
        global $wpdb;

        // Order matters: drop tables with foreign keys first.
        $tables = [
            'form_submissions',
            'sections',
            'style_variants',
            'pages',
            'components',
            'forms',
            'themes',
            'buttons',
            'navigation_menus',
            'templates',
        ];

        foreach ($tables as $table) {
            $fullTable = $this->prefix . $table;
            $wpdb->query("DROP TABLE IF EXISTS {$fullTable}"); // phpcs:ignore
        }
    }

    /**
     * Get the full table prefix (wp_prefix + npb_).
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Get charset collate string for table creation.
     */
    public function getCharsetCollate(): string
    {
        return $this->charsetCollate;
    }
}
