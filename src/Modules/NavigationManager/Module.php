<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\NavigationManager;

use NextPressBuilder\Core\AbstractModule;
use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\NavigationRepository;
use NextPressBuilder\Modules\NavigationManager\Controller\NavigationController;
use NextPressBuilder\Modules\NavigationManager\Service\NavigationService;

class Module extends AbstractModule
{
    public function slug(): string { return 'navigation-manager'; }
    public function name(): string { return 'Navigation Manager'; }
    public function version(): string { return '1.0.0'; }

    public function register(Container $container): void
    {
        parent::register($container);
        $container->singleton(NavigationRepository::class, fn() => new NavigationRepository());
        $container->singleton(NavigationService::class, fn(Container $c) => new NavigationService($c->make(NavigationRepository::class)));
        $container->singleton(NavigationController::class, fn(Container $c) => new NavigationController($c));
    }

    public function boot(): void
    {
        $this->container->make(NavigationService::class)->seedDefaults();
    }

    public function routes(): array
    {
        $ctrl = fn() => $this->container->make(NavigationController::class);
        return [
            ['method'=>'GET','path'=>'/navigation','callback'=>fn($r)=>$ctrl()->list($r),'permission'=>'__return_true'],
            ['method'=>'GET','path'=>'/navigation/(?P<slug>[a-z0-9_-]+)','callback'=>fn($r)=>$ctrl()->get($r),'permission'=>'__return_true'],
            ['method'=>'POST','path'=>'/navigation','callback'=>fn($r)=>$ctrl()->store($r),'permission'=>fn()=>$ctrl()->canManageNavigation()],
            ['method'=>'PUT','path'=>'/navigation/(?P<id>\d+)','callback'=>fn($r)=>$ctrl()->update($r),'permission'=>fn()=>$ctrl()->canManageNavigation()],
            ['method'=>'DELETE','path'=>'/navigation/(?P<id>\d+)','callback'=>fn($r)=>$ctrl()->destroy($r),'permission'=>fn()=>$ctrl()->canManageNavigation()],
        ];
    }
}
