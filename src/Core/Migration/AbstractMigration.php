<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Migration;

/**
 * Base migration class with helper methods.
 *
 * All concrete migrations extend this and implement up() and down().
 * Uses WordPress dbDelta() for safe table creation/updates.
 */
abstract class AbstractMigration implements MigrationInterface
{
    protected \wpdb $wpdb;
    protected string $prefix;
    protected string $charset;

    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix . 'npb_';
        $this->charset = $wpdb->get_charset_collate();
    }

    /**
     * Create a table using dbDelta (safe: won't break if table exists).
     */
    protected function createTable(string $name, string $columnsSql): void
    {
        $table = $this->prefix . $name;
        $sql = "CREATE TABLE {$table} (\n{$columnsSql}\n) {$this->charset};";

        dbDelta($sql);
    }

    /**
     * Drop a table.
     */
    protected function dropTable(string $name): void
    {
        $table = $this->prefix . $name;
        $this->wpdb->query("DROP TABLE IF EXISTS {$table}"); // phpcs:ignore
    }

    /**
     * Check if a table exists.
     */
    protected function tableExists(string $name): bool
    {
        $table = $this->prefix . $name;
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare("SHOW TABLES LIKE %s", $table)
        );
        return $result === $table;
    }

    /**
     * Get the full table name with prefix.
     */
    protected function table(string $name): string
    {
        return $this->prefix . $name;
    }
}
