<?php

declare(strict_types=1);

namespace NextPressBuilder;

/**
 * Plugin deactivation handler.
 *
 * NOTE: Does NOT remove data or capabilities.
 * Data is preserved so reactivation restores everything.
 * Full cleanup happens only on uninstall (Uninstaller class).
 */
class Deactivator
{
    /**
     * Run deactivation tasks.
     */
    public function deactivate(): void
    {
        $this->flushRewriteRules();
        $this->clearScheduledHooks();
    }

    /**
     * Flush rewrite rules to clean up REST endpoints.
     */
    private function flushRewriteRules(): void
    {
        flush_rewrite_rules();
    }

    /**
     * Clear any scheduled cron events.
     */
    private function clearScheduledHooks(): void
    {
        wp_clear_scheduled_hook( 'npb_webhook_retry' );
    }
}
