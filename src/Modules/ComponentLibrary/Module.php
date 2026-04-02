<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\ComponentLibrary;

use NextPressBuilder\Core\AbstractModule;
use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\ComponentRepository;
use NextPressBuilder\Core\Repository\VariantRepository;
use NextPressBuilder\Modules\ComponentLibrary\Controller\ComponentController;
use NextPressBuilder\Modules\ComponentLibrary\Service\ComponentService;
use NextPressBuilder\Modules\ComponentLibrary\Service\VariantService;

/**
 * Component Library Module.
 *
 * Manages all 20 section types and their style variants.
 * Like Elementor's Widget Manager — the catalog of everything
 * users can add to a page.
 */
class Module extends AbstractModule
{
    public function slug(): string
    {
        return 'component-library';
    }

    public function name(): string
    {
        return 'Component Library';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function dependencies(): array
    {
        return ['theme-manager'];
    }

    public function register(Container $container): void
    {
        parent::register($container);

        $container->singleton(ComponentRepository::class, fn() => new ComponentRepository());
        $container->singleton(VariantRepository::class, fn() => new VariantRepository());

        $container->singleton(ComponentService::class, fn(Container $c) => new ComponentService(
            $c->make(ComponentRepository::class),
        ));

        $container->singleton(VariantService::class, fn(Container $c) => new VariantService(
            $c->make(VariantRepository::class),
        ));

        $container->singleton(ComponentController::class, fn(Container $c) => new ComponentController($c));
    }

    public function boot(): void
    {
        // Seed built-in components if none exist
        global $wpdb;
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}npb_components");
        if ($count === 0) {
            $this->seedBuiltInComponents();
        }
    }

    /**
     * Seed only our core built-in components.
     */
    private function seedBuiltInComponents(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'npb_components';

        $components = [
            [
                'slug' => 'container',
                'name' => 'Container',
                'category' => 'structure',
                'description' => 'Flexbox layout container for structuring page sections.',
                'is_user_created' => 0,
                'is_active' => 1,
                'icon' => 'layout',
                'content_schema' => json_encode(['fields' => []]),
                'default_content' => json_encode([]),
            ],
            [
                'slug' => 'grid',
                'name' => 'Grid',
                'category' => 'structure',
                'description' => 'CSS Grid layout container.',
                'is_user_created' => 0,
                'is_active' => 1,
                'icon' => 'grid',
                'content_schema' => json_encode(['fields' => []]),
                'default_content' => json_encode([]),
            ],
            [
                'slug' => 'heading',
                'name' => 'Heading',
                'category' => 'basic',
                'description' => 'Heading text H1-H6 with full typography controls.',
                'is_user_created' => 0,
                'is_active' => 1,
                'icon' => 'type',
                'content_schema' => json_encode([
                    'fields' => [
                        ['key' => 'text', 'label' => 'Title', 'type' => 'textarea', 'default' => 'Add Your Heading Text Here'],
                        ['key' => 'tag', 'label' => 'HTML Tag', 'type' => 'select', 'default' => 'h2', 'options' => [
                            ['label' => 'H1', 'value' => 'h1'], ['label' => 'H2', 'value' => 'h2'],
                            ['label' => 'H3', 'value' => 'h3'], ['label' => 'H4', 'value' => 'h4'],
                            ['label' => 'H5', 'value' => 'h5'], ['label' => 'H6', 'value' => 'h6'],
                        ]],
                        ['key' => 'link', 'label' => 'Link', 'type' => 'url', 'default' => ''],
                    ],
                ]),
                'default_content' => json_encode([
                    'text' => 'Add Your Heading Text Here',
                    'tag' => 'h2',
                    'link' => '',
                ]),
            ],
        ];

        foreach ($components as $comp) {
            $wpdb->insert($table, $comp);
        }
    }

    public function routes(): array
    {
        $ctrl = fn() => $this->container->make(ComponentController::class);

        return [
            // Public.
            ['method'=>'GET','path'=>'/components','callback'=>fn($r) => $ctrl()->list($r),'permission'=>'__return_true'],
            ['method'=>'GET','path'=>'/components/(?P<slug>[a-z0-9_-]+)','callback'=>fn($r) => $ctrl()->get($r),'permission'=>'__return_true'],
            ['method'=>'GET','path'=>'/components/(?P<slug>[a-z0-9_-]+)/variants','callback'=>fn($r) => $ctrl()->getVariants($r),'permission'=>'__return_true'],
            // Admin.
            ['method'=>'GET','path'=>'/components/(?P<slug>[a-z0-9_-]+)/usage','callback'=>fn($r) => $ctrl()->usage($r),'permission'=>fn() => $ctrl()->canManageComponents()],
            ['method'=>'POST','path'=>'/components','callback'=>fn($r) => $ctrl()->store($r),'permission'=>fn() => $ctrl()->canManageComponents()],
            ['method'=>'PUT','path'=>'/components/(?P<id>\d+)','callback'=>fn($r) => $ctrl()->update($r),'permission'=>fn() => $ctrl()->canManageComponents()],
            ['method'=>'POST','path'=>'/components/(?P<id>\d+)/toggle','callback'=>fn($r) => $ctrl()->toggle($r),'permission'=>fn() => $ctrl()->canManageComponents()],
            ['method'=>'DELETE','path'=>'/components/(?P<id>\d+)','callback'=>fn($r) => $ctrl()->destroy($r),'permission'=>fn() => $ctrl()->canManageComponents()],
        ];
    }
}
