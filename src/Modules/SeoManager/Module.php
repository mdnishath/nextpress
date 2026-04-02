<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\SeoManager;

use NextPressBuilder\Core\AbstractModule;
use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\PageRepository;
use NextPressBuilder\Core\SettingsManager;
use NextPressBuilder\Modules\SeoManager\Controller\SeoController;
use NextPressBuilder\Modules\SeoManager\Service\SeoService;

class Module extends AbstractModule
{
    public function slug(): string { return 'seo-manager'; }
    public function name(): string { return 'SEO Manager'; }
    public function version(): string { return '1.0.0'; }

    public function register(Container $container): void
    {
        parent::register($container);
        if (!$container->has(PageRepository::class)) {
            $container->singleton(PageRepository::class, fn() => new PageRepository());
        }
        $container->singleton(SeoService::class, fn(Container $c) => new SeoService($c->make(SettingsManager::class)));
        $container->singleton(SeoController::class, fn(Container $c) => new SeoController($c));
    }

    public function routes(): array
    {
        $ctrl = fn() => $this->container->make(SeoController::class);
        return [
            ['method'=>'GET','path'=>'/seo/global','callback'=>fn($r)=>$ctrl()->getGlobal($r),'permission'=>'__return_true'],
            ['method'=>'PUT','path'=>'/seo/global','callback'=>fn($r)=>$ctrl()->updateGlobal($r),'permission'=>fn()=>$ctrl()->canManageSeo()],
            ['method'=>'GET','path'=>'/seo/page/(?P<slug>[a-z0-9_-]+)','callback'=>fn($r)=>$ctrl()->getPageSeo($r),'permission'=>'__return_true'],
            ['method'=>'GET','path'=>'/seo/sitemap','callback'=>fn($r)=>$ctrl()->getSitemap($r),'permission'=>'__return_true'],
            ['method'=>'GET','path'=>'/seo/redirects','callback'=>fn($r)=>$ctrl()->getRedirects($r),'permission'=>'__return_true'],
            ['method'=>'POST','path'=>'/seo/redirects','callback'=>fn($r)=>$ctrl()->addRedirect($r),'permission'=>fn()=>$ctrl()->canManageSeo()],
            ['method'=>'DELETE','path'=>'/seo/redirects/(?P<index>\d+)','callback'=>fn($r)=>$ctrl()->removeRedirect($r),'permission'=>fn()=>$ctrl()->canManageSeo()],
        ];
    }
}
