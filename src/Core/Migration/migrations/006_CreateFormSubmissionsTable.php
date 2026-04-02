<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Migration\migrations;

use NextPressBuilder\Core\Migration\AbstractMigration;

class CreateFormSubmissionsTable extends AbstractMigration
{
    public function version(): string
    {
        return '006';
    }

    public function up(): void
    {
        $this->createTable('form_submissions', "
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            form_id bigint(20) unsigned NOT NULL,
            data longtext NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(500) DEFAULT NULL,
            referrer varchar(500) DEFAULT NULL,
            status enum('unread','read','starred','archived','spam') DEFAULT 'unread',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_form_id (form_id),
            KEY idx_status (status),
            KEY idx_created_at (created_at)
        ");
    }

    public function down(): void
    {
        $this->dropTable('form_submissions');
    }
}
