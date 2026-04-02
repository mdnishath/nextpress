<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Migration\migrations;

use NextPressBuilder\Core\Migration\AbstractMigration;

class CreateStyleVariantsTable extends AbstractMigration
{
    public function version(): string
    {
        return '004';
    }

    public function up(): void
    {
        $this->createTable('style_variants', "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            component_slug varchar(100) NOT NULL,
            variant_slug varchar(100) NOT NULL,
            name varchar(255) NOT NULL,
            style longtext NOT NULL,
            preview_image varchar(500) DEFAULT NULL,
            is_premium tinyint(1) DEFAULT 0,
            sort_order int(10) unsigned DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_component_variant (component_slug,variant_slug),
            KEY idx_component (component_slug)
        ");
    }

    public function down(): void
    {
        $this->dropTable('style_variants');
    }
}
