<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Migration\migrations;

use NextPressBuilder\Core\Migration\AbstractMigration;

class CreateThemesTable extends AbstractMigration
{
    public function version(): string
    {
        return '007';
    }

    public function up(): void
    {
        $this->createTable('themes', "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            slug varchar(100) NOT NULL,
            name varchar(255) NOT NULL,
            is_active tinyint(1) DEFAULT 0,
            colors longtext NOT NULL,
            typography longtext NOT NULL,
            spacing longtext NOT NULL,
            buttons longtext NOT NULL,
            borders longtext DEFAULT NULL,
            shadows longtext DEFAULT NULL,
            dark_mode longtext DEFAULT NULL,
            custom_css text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_slug (slug),
            KEY idx_active (is_active)
        ");
    }

    public function down(): void
    {
        $this->dropTable('themes');
    }
}
