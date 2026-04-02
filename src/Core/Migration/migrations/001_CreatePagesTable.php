<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Migration\migrations;

use NextPressBuilder\Core\Migration\AbstractMigration;

class CreatePagesTable extends AbstractMigration
{
    public function version(): string
    {
        return '001';
    }

    public function up(): void
    {
        $this->createTable('pages', "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            wp_post_id bigint(20) unsigned DEFAULT NULL,
            slug varchar(255) NOT NULL,
            title varchar(255) NOT NULL,
            status enum('draft','published','archived') DEFAULT 'draft',
            page_type enum('page','header','footer','component') DEFAULT 'page',
            header_id bigint(20) unsigned DEFAULT NULL,
            footer_id bigint(20) unsigned DEFAULT NULL,
            seo_title varchar(255) DEFAULT NULL,
            seo_description text DEFAULT NULL,
            seo_keywords text DEFAULT NULL,
            og_image varchar(500) DEFAULT NULL,
            schema_type varchar(100) DEFAULT NULL,
            schema_data longtext DEFAULT NULL,
            template_id varchar(100) DEFAULT NULL,
            settings longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_slug (slug),
            KEY idx_status (status),
            KEY idx_page_type (page_type),
            KEY idx_wp_post_id (wp_post_id)
        ");
    }

    public function down(): void
    {
        $this->dropTable('pages');
    }
}
