<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Migration\migrations;

use NextPressBuilder\Core\Migration\AbstractMigration;

class CreateFormsTable extends AbstractMigration
{
    public function version(): string
    {
        return '005';
    }

    public function up(): void
    {
        $this->createTable('forms', "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            slug varchar(100) NOT NULL,
            name varchar(255) NOT NULL,
            fields longtext NOT NULL,
            settings longtext NOT NULL,
            conditional_logic longtext DEFAULT NULL,
            multi_step longtext DEFAULT NULL,
            styling longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_slug (slug)
        ");
    }

    public function down(): void
    {
        $this->dropTable('forms');
    }
}
