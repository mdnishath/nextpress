<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Migration\migrations;

use NextPressBuilder\Core\Migration\AbstractMigration;

class CreateNavigationMenusTable extends AbstractMigration
{
    public function version(): string
    {
        return '009';
    }

    public function up(): void
    {
        $this->createTable('navigation_menus', "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            slug varchar(100) NOT NULL,
            name varchar(255) NOT NULL,
            location enum('header','footer','sidebar','custom') DEFAULT 'header',
            items longtext NOT NULL,
            settings longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_slug (slug),
            KEY idx_location (location)
        ");
    }

    public function down(): void
    {
        $this->dropTable('navigation_menus');
    }
}
