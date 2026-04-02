<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\SecurityManager;

use NextPressBuilder\Core\AbstractModule;
use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\SettingsManager;

/**
 * Security hardening: audit logging of admin actions.
 * Rate limiting is handled centrally in RestApiManager.
 */
class Module extends AbstractModule
{
    public function slug(): string { return 'security-manager'; }
    public function name(): string { return 'Security Manager'; }
    public function version(): string { return '1.0.0'; }

    public function register(Container $container): void
    {
        parent::register($container);
    }

    public function boot(): void
    {
        // Log admin actions for audit trail.
        add_action('npb/theme-manager/theme_activated', function($themeId) {
            $this->logAction('theme_activated', ['theme_id' => $themeId]);
        });
    }

    /**
     * Log an admin action for audit trail.
     *
     * @param array<string, mixed> $data
     */
    private function logAction(string $action, array $data = []): void
    {
        /** @var SettingsManager $settings */
        $settings = $this->container->make(SettingsManager::class);
        $log = $settings->getArray('audit_log', []);

        if (count($log) >= 200) {
            $log = array_slice($log, -199);
        }

        $log[] = [
            'action'    => $action,
            'data'      => $data,
            'user_id'   => get_current_user_id(),
            'timestamp' => gmdate('Y-m-d H:i:s'),
            'ip'        => $_SERVER['REMOTE_ADDR'] ?? '',
        ];

        $settings->set('audit_log', $log);
    }

    public function routes(): array
    {
        return [
            [
                'method'     => 'GET',
                'path'       => '/security/audit-log',
                'callback'   => function() {
                    $settings = $this->container->make(SettingsManager::class);
                    return new \WP_REST_Response([
                        'success' => true,
                        'data'    => array_reverse($settings->getArray('audit_log', [])),
                    ], 200);
                },
                'permission' => function() { return current_user_can('npb_manage_settings'); },
            ],
        ];
    }
}
