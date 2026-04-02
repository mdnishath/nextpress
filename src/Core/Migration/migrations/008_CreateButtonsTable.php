<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Migration\migrations;

use NextPressBuilder\Core\Migration\AbstractMigration;

class CreateButtonsTable extends AbstractMigration
{
    public function version(): string
    {
        return '008';
    }

    public function up(): void
    {
        $this->createTable('buttons', "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            slug varchar(100) NOT NULL,
            name varchar(255) NOT NULL,
            preset longtext NOT NULL,
            is_default tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_slug (slug)
        ");
    }

    public function down(): void
    {
        $this->dropTable('buttons');
    }
}
