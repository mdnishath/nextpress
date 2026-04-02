<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\ThemeManager;

use NextPressBuilder\Core\AbstractModule;
use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\ThemeRepository;
use NextPressBuilder\Modules\ThemeManager\Controller\ThemeController;
use NextPressBuilder\Modules\ThemeManager\Service\ColorUtility;
use NextPressBuilder\Modules\ThemeManager\Service\CssVariableGenerator;
use NextPressBuilder\Modules\ThemeManager\Service\ThemeService;

/**
 * Theme Manager Module.
 *
 * First real module! Manages the design token system:
 * colors, typography, spacing, shadows, borders, dark mode.
 * Outputs CSS custom properties for the Next.js frontend.
 */
class Module extends AbstractModule
{
    public function slug(): string
    {
        return 'theme-manager';
    }

    public function name(): string
    {
        return 'Theme Manager';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    /**
     * Register services in the DI container.
     */
    public function register(Container $container): void
    {
        parent::register($container);

        $container->singleton(ColorUtility::class, fn() => new ColorUtility());

        $container->singleton(CssVariableGenerator::class, fn() => new CssVariableGenerator());

        $container->singleton(ThemeRepository::class, fn() => new ThemeRepository());

        $container->singleton(ThemeService::class, fn(Container $c) => new ThemeService(
            $c->make(ThemeRepository::class),
            $c->make(ColorUtility::class),
            $c->make(CssVariableGenerator::class),
        ));

        $container->singleton(ThemeController::class, fn(Container $c) => new ThemeController($c));
    }

    /**
     * Boot: seed preset themes if none exist.
     */
    public function boot(): void
    {
        /** @var ThemeService $service */
        $service = $this->container->make(ThemeService::class);
        $service->seedPresets();
    }

    /**
     * REST API routes for this module.
     *
     * @return array<int, array<string, mixed>>
     */
    public function routes(): array
    {
        $ctrl = fn() => $this->container->make(ThemeController::class);

        return [
            // Public endpoints.
            [
                'method'     => 'GET',
                'path'       => '/theme',
                'callback'   => fn($r) => $ctrl()->getActive($r),
                'permission' => '__return_true',
            ],
            [
                'method'     => 'GET',
                'path'       => '/theme/css-variables',
                'callback'   => fn($r) => $ctrl()->getCssVariables($r),
                'permission' => '__return_true',
            ],
            [
                'method'     => 'GET',
                'path'       => '/theme/fonts',
                'callback'   => fn($r) => $ctrl()->getFonts($r),
                'permission' => '__return_true',
            ],

            // Admin endpoints.
            [
                'method'     => 'GET',
                'path'       => '/themes',
                'callback'   => fn($r) => $ctrl()->list($r),
                'permission' => fn() => $ctrl()->canManageThemes(),
            ],
            [
                'method'     => 'GET',
                'path'       => '/themes/(?P<id>\d+)',
                'callback'   => fn($r) => $ctrl()->get($r),
                'permission' => fn() => $ctrl()->canManageThemes(),
            ],
            [
                'method'     => 'POST',
                'path'       => '/themes',
                'callback'   => fn($r) => $ctrl()->store($r),
                'permission' => fn() => $ctrl()->canManageThemes(),
            ],
            [
                'method'     => 'PUT',
                'path'       => '/themes/(?P<id>\d+)',
                'callback'   => fn($r) => $ctrl()->update($r),
                'permission' => fn() => $ctrl()->canManageThemes(),
            ],
            [
                'method'     => 'PUT',
                'path'       => '/themes/(?P<id>\d+)/activate',
                'callback'   => fn($r) => $ctrl()->activate($r),
                'permission' => fn() => $ctrl()->canManageThemes(),
            ],
            [
                'method'     => 'DELETE',
                'path'       => '/themes/(?P<id>\d+)',
                'callback'   => fn($r) => $ctrl()->destroy($r),
                'permission' => fn() => $ctrl()->canManageThemes(),
            ],
            [
                'method'     => 'GET',
                'path'       => '/themes/(?P<id>\d+)/export',
                'callback'   => fn($r) => $ctrl()->export($r),
                'permission' => fn() => $ctrl()->canManageThemes(),
            ],
            [
                'method'     => 'POST',
                'path'       => '/themes/import',
                'callback'   => fn($r) => $ctrl()->import($r),
                'permission' => fn() => $ctrl()->canManageThemes(),
            ],
        ];
    }
}
