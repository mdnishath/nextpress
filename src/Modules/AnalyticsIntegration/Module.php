<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\AnalyticsIntegration;

use NextPressBuilder\Core\AbstractModule;
use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\SettingsManager;

/**
 * Analytics integration: GA4, GTM, Facebook Pixel config.
 * Outputs config as JSON — Next.js handles actual script loading.
 */
class Module extends AbstractModule
{
    public function slug(): string { return 'analytics-integration'; }
    public function name(): string { return 'Analytics Integration'; }
    public function version(): string { return '1.0.0'; }

    public function register(Container $container): void
    {
        parent::register($container);
    }

    public function routes(): array
    {
        return [
            [
                'method'     => 'GET',
                'path'       => '/analytics/config',
                'callback'   => function() {
                    $s = $this->container->make(SettingsManager::class);
                    return new \WP_REST_Response(['success'=>true,'data'=>[
                        'ga4_measurement_id' => $s->getString('analytics_ga4_id'),
                        'gtm_container_id'   => $s->getString('analytics_gtm_id'),
                        'fb_pixel_id'        => $s->getString('analytics_fb_pixel_id'),
                        'custom_head_scripts'=> $s->getString('analytics_head_scripts'),
                        'custom_body_scripts'=> $s->getString('analytics_body_scripts'),
                    ]], 200);
                },
                'permission' => '__return_true',
            ],
            [
                'method'     => 'PUT',
                'path'       => '/analytics/config',
                'callback'   => function(\WP_REST_Request $request) {
                    $s = $this->container->make(SettingsManager::class);
                    $data = $request->get_json_params();
                    $keys = ['ga4_id'=>'analytics_ga4_id','gtm_id'=>'analytics_gtm_id','fb_pixel_id'=>'analytics_fb_pixel_id','head_scripts'=>'analytics_head_scripts','body_scripts'=>'analytics_body_scripts'];
                    foreach ($keys as $k => $sk) {
                        if (isset($data[$k])) $s->set($sk, sanitize_text_field($data[$k]));
                    }
                    return new \WP_REST_Response(['success'=>true,'data'=>['message'=>'Analytics config updated.']], 200);
                },
                'permission' => function() { return current_user_can('npb_manage_settings'); },
            ],
        ];
    }
}
