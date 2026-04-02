<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Migration\migrations;

use NextPressBuilder\Core\Migration\AbstractMigration;

class CreateTemplatesTable extends AbstractMigration
{
    public function version(): string
    {
        return '010';
    }

    public function up(): void
    {
        $this->createTable('templates', "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            slug varchar(100) NOT NULL,
            name varchar(255) NOT NULL,
            business_type varchar(100) NOT NULL,
            description text DEFAULT NULL,
            preview_image varchar(500) DEFAULT NULL,
            data longtext NOT NULL,
            version varchar(20) DEFAULT '1.0.0',
            is_premium tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_slug (slug),
            KEY idx_business_type (business_type)
        ");
    }

    public function down(): void
    {
        $this->dropTable('templates');
    }
}
