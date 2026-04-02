<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\TemplateLibrary;

use NextPressBuilder\Core\AbstractModule;
use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\FormRepository;
use NextPressBuilder\Core\Repository\NavigationRepository;
use NextPressBuilder\Core\Repository\PageRepository;
use NextPressBuilder\Core\Repository\SectionRepository;
use NextPressBuilder\Core\Repository\TemplateRepository;
use NextPressBuilder\Core\Repository\ThemeRepository;
use NextPressBuilder\Core\SettingsManager;
use NextPressBuilder\Modules\TemplateLibrary\Controller\TemplateController;
use NextPressBuilder\Modules\TemplateLibrary\Service\TemplateImportService;

class Module extends AbstractModule
{
    public function slug(): string { return 'template-library'; }
    public function name(): string { return 'Template Library'; }
    public function version(): string { return '1.0.0'; }
    public function dependencies(): array { return ['page-builder', 'theme-manager', 'form-builder', 'navigation-manager']; }

    public function register(Container $container): void
    {
        parent::register($container);

        if (!$container->has(TemplateRepository::class)) {
            $container->singleton(TemplateRepository::class, fn() => new TemplateRepository());
        }

        $container->singleton(TemplateImportService::class, fn(Container $c) => new TemplateImportService(
            $c->make(PageRepository::class),
            $c->make(SectionRepository::class),
            $c->make(ThemeRepository::class),
            $c->make(NavigationRepository::class),
            $c->make(FormRepository::class),
            $c->make(SettingsManager::class),
        ));

        $container->singleton(TemplateController::class, fn(Container $c) => new TemplateController($c));
    }

    public function boot(): void
    {
        $this->seedTemplates();
    }

    private function seedTemplates(): void
    {
        $repo = $this->container->make(TemplateRepository::class);
        if ($repo->count() > 0) return;

        $fixturesDir = __DIR__ . '/Fixtures/templates/';
        if (!is_dir($fixturesDir)) return;

        $files = glob($fixturesDir . '*.json');
        if (!$files) return;

        foreach ($files as $file) {
            $json = file_get_contents($file);
            if (!$json) continue;

            $template = json_decode($json, true);
            if (!$template) continue;

            $repo->create([
                'slug'          => $template['slug'] ?? basename($file, '.json'),
                'name'          => $template['name'] ?? '',
                'business_type' => $template['business_type'] ?? '',
                'description'   => $template['description'] ?? '',
                'data'          => $template['data'] ?? [],
            ]);
        }
    }

    public function routes(): array
    {
        $ctrl = fn() => $this->container->make(TemplateController::class);
        return [
            ['method'=>'GET','path'=>'/templates','callback'=>fn($r)=>$ctrl()->list($r),'permission'=>'__return_true'],
            ['method'=>'GET','path'=>'/templates/(?P<slug>[a-z0-9_-]+)','callback'=>fn($r)=>$ctrl()->get($r),'permission'=>'__return_true'],
            ['method'=>'POST','path'=>'/templates/(?P<slug>[a-z0-9_-]+)/import','callback'=>fn($r)=>$ctrl()->import($r),'permission'=>fn()=>$ctrl()->canManageTemplates()],
        ];
    }
}
