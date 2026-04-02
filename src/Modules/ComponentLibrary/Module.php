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
        $this->ensureBuiltInsExist();
    }

    /**
     * Ensure all built-in components exist. Inserts missing ones without touching existing.
     */
    private function ensureBuiltInsExist(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'npb_components';

        $builtIns = $this->getBuiltInComponents();

        foreach ($builtIns as $comp) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE slug = %s",
                $comp['slug']
            ));
            if (!$exists) {
                $wpdb->insert($table, $comp);
            }
        }
    }

    /**
     * All built-in component definitions.
     */
    private function getBuiltInComponents(): array
    {
        return [
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
            [
                'slug' => 'text_editor',
                'name' => 'Text Editor',
                'category' => 'basic',
                'description' => 'Rich text with formatting.',
                'is_user_created' => 0,
                'is_active' => 1,
                'icon' => 'align-left',
                'content_schema' => json_encode([
                    'fields' => [
                        ['key' => 'content', 'label' => 'Content', 'type' => 'richtext', 'default' => '<p>Add your text here. Click to edit.</p>'],
                        ['key' => 'alignment', 'label' => 'Alignment', 'type' => 'select', 'default' => 'left', 'options' => [
                            ['label' => 'Left', 'value' => 'left'],
                            ['label' => 'Center', 'value' => 'center'],
                            ['label' => 'Right', 'value' => 'right'],
                        ]],
                    ],
                ]),
                'default_content' => json_encode([
                    'content' => '<p>Add your text here. Click to edit.</p>',
                    'alignment' => 'left',
                ]),
            ],
            [
                'slug' => 'image',
                'name' => 'Image',
                'category' => 'basic',
                'description' => 'Single image with caption.',
                'is_user_created' => 0,
                'is_active' => 1,
                'icon' => 'image',
                'content_schema' => json_encode([
                    'fields' => [
                        ['key' => 'src', 'label' => 'Image', 'type' => 'image', 'default' => ''],
                        ['key' => 'alt', 'label' => 'Alt Text', 'type' => 'text', 'default' => ''],
                        ['key' => 'caption', 'label' => 'Caption', 'type' => 'text', 'default' => ''],
                        ['key' => 'link', 'label' => 'Link', 'type' => 'url', 'default' => ''],
                        ['key' => 'alignment', 'label' => 'Alignment', 'type' => 'select', 'default' => 'center', 'options' => [
                            ['label' => 'Left', 'value' => 'left'],
                            ['label' => 'Center', 'value' => 'center'],
                            ['label' => 'Right', 'value' => 'right'],
                        ]],
                    ],
                ]),
                'default_content' => json_encode([
                    'src' => '',
                    'alt' => '',
                    'caption' => '',
                    'link' => '',
                    'alignment' => 'center',
                ]),
            ],
            [
                'slug' => 'button',
                'name' => 'Button',
                'category' => 'basic',
                'description' => 'Call-to-action button.',
                'is_user_created' => 0,
                'is_active' => 1,
                'icon' => 'mouse-pointer',
                'content_schema' => json_encode([
                    'fields' => [
                        ['key' => 'text', 'label' => 'Text', 'type' => 'text', 'default' => 'Click Here'],
                        ['key' => 'link', 'label' => 'Link', 'type' => 'url', 'default' => '#'],
                        ['key' => 'alignment', 'label' => 'Alignment', 'type' => 'select', 'default' => 'left', 'options' => [
                            ['label' => 'Left', 'value' => 'left'],
                            ['label' => 'Center', 'value' => 'center'],
                            ['label' => 'Right', 'value' => 'right'],
                        ]],
                        ['key' => 'size', 'label' => 'Size', 'type' => 'select', 'default' => 'medium', 'options' => [
                            ['label' => 'Small', 'value' => 'small'],
                            ['label' => 'Medium', 'value' => 'medium'],
                            ['label' => 'Large', 'value' => 'large'],
                        ]],
                    ],
                ]),
                'default_content' => json_encode([
                    'text' => 'Click Here',
                    'link' => '#',
                    'alignment' => 'left',
                    'size' => 'medium',
                ]),
            ],
            [
                'slug' => 'spacer',
                'name' => 'Spacer',
                'category' => 'basic',
                'description' => 'Empty space between elements.',
                'is_user_created' => 0,
                'is_active' => 1,
                'icon' => 'minus',
                'content_schema' => json_encode([
                    'fields' => [
                        ['key' => 'height', 'label' => 'Height (px)', 'type' => 'number', 'default' => 40, 'min' => 1, 'max' => 500],
                    ],
                ]),
                'default_content' => json_encode([
                    'height' => 40,
                ]),
            ],
        ];
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
