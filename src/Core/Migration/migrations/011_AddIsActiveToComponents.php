<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Migration\migrations;

use NextPressBuilder\Core\Migration\AbstractMigration;

class AddIsActiveToComponents extends AbstractMigration
{
    public function version(): string
    {
        return '011';
    }

    public function up(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'npb_components';

        // Add is_active column (default 1 = active)
        $col = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'is_active'");
        if (empty($col)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN is_active tinyint(1) NOT NULL DEFAULT 1 AFTER is_user_created");
        }

        // Add icon column
        $col2 = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'icon'");
        if (empty($col2)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN icon varchar(50) DEFAULT 'box' AFTER is_active");
        }
    }

    public function down(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'npb_components';
        $wpdb->query("ALTER TABLE {$table} DROP COLUMN IF EXISTS is_active");
        $wpdb->query("ALTER TABLE {$table} DROP COLUMN IF EXISTS icon");
    }
}
