<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Migration\migrations;

use NextPressBuilder\Core\Migration\AbstractMigration;

class CreateSectionsTable extends AbstractMigration
{
    public function version(): string
    {
        return '002';
    }

    public function up(): void
    {
        $this->createTable('sections', "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            page_id bigint(20) unsigned NOT NULL,
            parent_id bigint(20) unsigned DEFAULT NULL,
            section_type varchar(100) NOT NULL,
            variant_id varchar(100) DEFAULT 'default',
            sort_order int(10) unsigned DEFAULT 0,
            enabled tinyint(1) DEFAULT 1,
            content longtext NOT NULL,
            style longtext DEFAULT NULL,
            responsive longtext DEFAULT NULL,
            visibility longtext DEFAULT NULL,
            layout longtext DEFAULT NULL,
            animation varchar(50) DEFAULT NULL,
            custom_css text DEFAULT NULL,
            custom_id varchar(100) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_page_id (page_id),
            KEY idx_parent_id (parent_id),
            KEY idx_sort_order (page_id,sort_order)
        ");
    }

    public function down(): void
    {
        $this->dropTable('sections');
    }
}
