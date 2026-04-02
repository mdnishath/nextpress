<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Migration\migrations;

use NextPressBuilder\Core\Migration\AbstractMigration;

class CreateComponentsTable extends AbstractMigration
{
    public function version(): string
    {
        return '003';
    }

    public function up(): void
    {
        $this->createTable('components', "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            slug varchar(100) NOT NULL,
            name varchar(255) NOT NULL,
            category varchar(100) NOT NULL,
            description text DEFAULT NULL,
            is_user_created tinyint(1) DEFAULT 0,
            content_schema longtext NOT NULL,
            default_content longtext DEFAULT NULL,
            default_style longtext DEFAULT NULL,
            preview_image varchar(500) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_slug (slug),
            KEY idx_category (category)
        ");
    }

    public function down(): void
    {
        $this->dropTable('components');
    }
}
