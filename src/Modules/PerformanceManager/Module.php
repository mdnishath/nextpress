<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\PerformanceManager;

use NextPressBuilder\Core\AbstractModule;
use NextPressBuilder\Core\Container;

/**
 * Performance optimization: API response caching via WordPress object cache.
 * Cache headers are handled centrally in CacheHeaders middleware.
 */
class Module extends AbstractModule
{
    public function slug(): string { return 'performance-manager'; }
    public function name(): string { return 'Performance Manager'; }
    public function version(): string { return '1.0.0'; }

    public function register(Container $container): void
    {
        parent::register($container);
    }

    public function boot(): void
    {
        // Flush object cache when content changes.
        add_action('npb_form_submitted', function() { wp_cache_flush_group('npb'); });
    }

    public function routes(): array
    {
        return [
            [
                'method'     => 'POST',
                'path'       => '/performance/flush-cache',
                'callback'   => function() {
                    wp_cache_flush();
                    return new \WP_REST_Response(['success'=>true,'data'=>['message'=>'Cache flushed.']], 200);
                },
                'permission' => function() { return current_user_can('npb_manage_settings'); },
            ],
        ];
    }
}
