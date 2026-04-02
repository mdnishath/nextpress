<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\ButtonManager;

use NextPressBuilder\Core\AbstractModule;
use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\ButtonRepository;
use NextPressBuilder\Modules\ButtonManager\Controller\ButtonController;
use NextPressBuilder\Modules\ButtonManager\Service\ButtonService;

class Module extends AbstractModule
{
    public function slug(): string { return 'button-manager'; }
    public function name(): string { return 'Button Manager'; }
    public function version(): string { return '1.0.0'; }
    public function dependencies(): array { return ['theme-manager']; }

    public function register(Container $container): void
    {
        parent::register($container);
        $container->singleton(ButtonRepository::class, fn() => new ButtonRepository());
        $container->singleton(ButtonService::class, fn(Container $c) => new ButtonService($c->make(ButtonRepository::class)));
        $container->singleton(ButtonController::class, fn(Container $c) => new ButtonController($c));
    }

    public function boot(): void
    {
        $this->container->make(ButtonService::class)->seedDefaults();
    }

    public function routes(): array
    {
        $ctrl = fn() => $this->container->make(ButtonController::class);
        return [
            ['method'=>'GET','path'=>'/buttons','callback'=>fn($r)=>$ctrl()->list($r),'permission'=>'__return_true'],
            ['method'=>'GET','path'=>'/buttons/(?P<slug>[a-z0-9_-]+)','callback'=>fn($r)=>$ctrl()->get($r),'permission'=>'__return_true'],
            ['method'=>'POST','path'=>'/buttons','callback'=>fn($r)=>$ctrl()->store($r),'permission'=>fn()=>$ctrl()->canManageThemes()],
            ['method'=>'PUT','path'=>'/buttons/(?P<id>\d+)','callback'=>fn($r)=>$ctrl()->update($r),'permission'=>fn()=>$ctrl()->canManageThemes()],
            ['method'=>'DELETE','path'=>'/buttons/(?P<id>\d+)','callback'=>fn($r)=>$ctrl()->destroy($r),'permission'=>fn()=>$ctrl()->canManageThemes()],
        ];
    }
}
