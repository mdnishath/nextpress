<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\SchemaManager;

use NextPressBuilder\Core\AbstractModule;
use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\PageRepository;
use NextPressBuilder\Core\Repository\SectionRepository;
use NextPressBuilder\Core\SettingsManager;
use NextPressBuilder\Modules\SchemaManager\Controller\SchemaController;
use NextPressBuilder\Modules\SchemaManager\Service\SchemaService;

class Module extends AbstractModule
{
    public function slug(): string { return 'schema-manager'; }
    public function name(): string { return 'Schema Manager'; }
    public function version(): string { return '1.0.0'; }
    public function dependencies(): array { return ['seo-manager']; }

    public function register(Container $container): void
    {
        parent::register($container);
        if (!$container->has(SectionRepository::class)) {
            $container->singleton(SectionRepository::class, fn() => new SectionRepository());
        }
        $container->singleton(SchemaService::class, fn(Container $c) => new SchemaService($c->make(SettingsManager::class)));
        $container->singleton(SchemaController::class, fn(Container $c) => new SchemaController($c));
    }

    public function routes(): array
    {
        $ctrl = fn() => $this->container->make(SchemaController::class);
        return [
            ['method'=>'GET','path'=>'/schema/global','callback'=>fn($r)=>$ctrl()->getGlobal($r),'permission'=>'__return_true'],
            ['method'=>'GET','path'=>'/schema/page/(?P<slug>[a-z0-9_-]+)','callback'=>fn($r)=>$ctrl()->getPageSchema($r),'permission'=>'__return_true'],
            ['method'=>'GET','path'=>'/schema/types','callback'=>fn($r)=>$ctrl()->getTypes($r),'permission'=>'__return_true'],
        ];
    }
}
