<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\RevalidationManager;

use NextPressBuilder\Core\AbstractModule;
use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\SettingsManager;
use NextPressBuilder\Core\WebhookManager;

/**
 * Manages Next.js ISR revalidation: triggers webhooks on content changes,
 * manual revalidation, connectivity health check, delivery log.
 */
class Module extends AbstractModule
{
    public function slug(): string { return 'revalidation-manager'; }
    public function name(): string { return 'Revalidation Manager'; }
    public function version(): string { return '1.0.0'; }

    public function register(Container $container): void
    {
        parent::register($container);
    }

    public function boot(): void
    {
        // Auto-trigger revalidation on WordPress core content changes.
        $hooks = ['save_post', 'delete_post'];
        foreach ($hooks as $hook) {
            add_action($hook, function () {
                $this->revalidateAll('wp_content_changed');
            }, 99);
        }

        // Auto-trigger on NextPress REST API mutations.
        // Fires after any REST response that modifies data.
        add_filter('rest_post_dispatch', function ($response, $server, $request) {
            $route = $request->get_route();
            $method = $request->get_method();

            // Only trigger on our namespace + write methods.
            if (!str_starts_with($route, '/npb/v1')) return $response;
            if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) return $response;

            // Skip auth/settings/revalidate endpoints to avoid loops.
            if (str_contains($route, '/auth/') || str_contains($route, '/revalidate/')) return $response;

            // Only if response was successful.
            $status = $response->get_status();
            if ($status >= 200 && $status < 300) {
                $this->revalidateAll("rest_api_{$method}");
            }

            return $response;
        }, 999, 3);
    }

    /**
     * Trigger revalidation of all paths.
     */
    private function revalidateAll(string $reason): void
    {
        static $fired = false;
        if ($fired) return; // Only fire once per request.
        $fired = true;

        $s = $this->container->make(SettingsManager::class);
        $url = $s->getString('nextjs_revalidation_url');
        if (!$url) return;

        $wh = $this->container->make(WebhookManager::class);
        $wh->triggerRevalidation(['/'], $reason);
    }

    public function routes(): array
    {
        return [
            // Manual trigger.
            [
                'method' => 'POST',
                'path' => '/revalidate/trigger',
                'callback' => function(\WP_REST_Request $request) {
                    $data = $request->get_json_params();
                    $paths = $data['paths'] ?? ['/'];
                    $wh = $this->container->make(WebhookManager::class);
                    $result = $wh->triggerRevalidation($paths, 'manual');
                    return new \WP_REST_Response(['success'=>true,'data'=>['triggered'=>$result,'paths'=>$paths]], 200);
                },
                'permission' => function() { return current_user_can('npb_manage_settings'); },
            ],
            // Delivery log.
            [
                'method' => 'GET',
                'path' => '/revalidate/log',
                'callback' => function() {
                    $wh = $this->container->make(WebhookManager::class);
                    return new \WP_REST_Response(['success'=>true,'data'=>$wh->getLog()], 200);
                },
                'permission' => function() { return current_user_can('npb_manage_settings'); },
            ],
            // Health check — can Next.js be reached?
            [
                'method' => 'GET',
                'path' => '/revalidate/status',
                'callback' => function() {
                    $s = $this->container->make(SettingsManager::class);
                    $url = $s->getString('nextjs_frontend_url');
                    if (!$url) return new \WP_REST_Response(['success'=>true,'data'=>['status'=>'not_configured']], 200);

                    $response = wp_remote_get($url, ['timeout' => 5]);
                    $ok = !is_wp_error($response) && wp_remote_retrieve_response_code($response) < 500;

                    return new \WP_REST_Response(['success'=>true,'data'=>[
                        'status'      => $ok ? 'reachable' : 'unreachable',
                        'url'         => $url,
                        'status_code' => is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response),
                    ]], 200);
                },
                'permission' => function() { return current_user_can('npb_manage_settings'); },
            ],
        ];
    }
}
