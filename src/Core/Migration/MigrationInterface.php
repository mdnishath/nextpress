<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Migration;

/**
 * Contract for database migrations.
 */
interface MigrationInterface
{
    /**
     * Version string for ordering (e.g., '001', '002').
     */
    public function version(): string;

    /**
     * Run the migration (create/alter tables).
     */
    public function up(): void;

    /**
     * Reverse the migration (drop tables). Used in testing/dev only.
     */
    public function down(): void;
}
