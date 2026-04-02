<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\PageBuilder;

use NextPressBuilder\Core\AbstractModule;
use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\ComponentRepository;
use NextPressBuilder\Core\Repository\PageRepository;
use NextPressBuilder\Core\Repository\SectionRepository;
use NextPressBuilder\Core\Repository\VariantRepository;
use NextPressBuilder\Core\WebhookManager;
use NextPressBuilder\Modules\PageBuilder\Controller\PageController;
use NextPressBuilder\Modules\PageBuilder\Controller\SectionController;
use NextPressBuilder\Modules\PageBuilder\Service\PageService;
use NextPressBuilder\Modules\PageBuilder\Service\SectionService;

class Module extends AbstractModule
{
    public function slug(): string { return 'page-builder'; }
    public function name(): string { return 'Page Builder'; }
    public function version(): string { return '1.0.0'; }
    public function dependencies(): array { return ['component-library', 'theme-manager']; }

    public function register(Container $container): void
    {
        parent::register($container);

        // Ensure repos are registered.
        if (!$container->has(PageRepository::class)) $container->singleton(PageRepository::class, fn() => new PageRepository());
        if (!$container->has(SectionRepository::class)) $container->singleton(SectionRepository::class, fn() => new SectionRepository());

        $container->singleton(PageService::class, fn(Container $c) => new PageService(
            $c->make(PageRepository::class),
            $c->make(SectionRepository::class),
            $c->make(WebhookManager::class),
        ));

        $container->singleton(SectionService::class, fn(Container $c) => new SectionService(
            $c->make(SectionRepository::class),
            $c->make(ComponentRepository::class),
            $c->make(VariantRepository::class),
        ));

        $container->singleton(PageController::class, fn(Container $c) => new PageController($c));
        $container->singleton(SectionController::class, fn(Container $c) => new SectionController($c));
    }

    public function routes(): array
    {
        $pc = fn() => $this->container->make(PageController::class);
        $sc = fn() => $this->container->make(SectionController::class);

        return [
            // Pages — public.
            ['method'=>'GET','path'=>'/pages','callback'=>fn($r)=>$pc()->list($r),'permission'=>'__return_true'],
            ['method'=>'GET','path'=>'/pages/(?P<slug>[a-z0-9_-]+)','callback'=>fn($r)=>$pc()->get($r),'permission'=>'__return_true'],
            // Pages — admin.
            ['method'=>'POST','path'=>'/pages','callback'=>fn($r)=>$pc()->store($r),'permission'=>fn()=>$pc()->canEditPages()],
            ['method'=>'PUT','path'=>'/pages/(?P<id>\d+)','callback'=>fn($r)=>$pc()->update($r),'permission'=>fn()=>$pc()->canEditPages()],
            ['method'=>'PUT','path'=>'/pages/(?P<id>\d+)/publish','callback'=>fn($r)=>$pc()->publish($r),'permission'=>fn()=>$pc()->canEditPages()],
            ['method'=>'PUT','path'=>'/pages/(?P<id>\d+)/unpublish','callback'=>fn($r)=>$pc()->unpublish($r),'permission'=>fn()=>$pc()->canEditPages()],
            ['method'=>'POST','path'=>'/pages/(?P<id>\d+)/duplicate','callback'=>fn($r)=>$pc()->duplicate($r),'permission'=>fn()=>$pc()->canEditPages()],
            ['method'=>'DELETE','path'=>'/pages/(?P<id>\d+)','callback'=>fn($r)=>$pc()->destroy($r),'permission'=>fn()=>$pc()->canEditPages()],
            ['method'=>'GET','path'=>'/pages/(?P<id>\d+)/export','callback'=>fn($r)=>$pc()->export($r),'permission'=>fn()=>$pc()->canEditPages()],
            ['method'=>'POST','path'=>'/pages/import','callback'=>fn($r)=>$pc()->import($r),'permission'=>fn()=>$pc()->canEditPages()],
            // Sections.
            ['method'=>'GET','path'=>'/pages/(?P<id>\d+)/sections','callback'=>fn($r)=>$sc()->listByPage($r),'permission'=>'__return_true'],
            ['method'=>'POST','path'=>'/pages/(?P<id>\d+)/sections','callback'=>fn($r)=>$sc()->store($r),'permission'=>fn()=>$sc()->canEditPages()],
            ['method'=>'POST','path'=>'/pages/(?P<id>\d+)/sections/reorder','callback'=>fn($r)=>$sc()->reorder($r),'permission'=>fn()=>$sc()->canEditPages()],
            ['method'=>'PUT','path'=>'/sections/(?P<id>\d+)','callback'=>fn($r)=>$sc()->update($r),'permission'=>fn()=>$sc()->canEditPages()],
            ['method'=>'PUT','path'=>'/sections/(?P<id>\d+)/content','callback'=>fn($r)=>$sc()->updateContent($r),'permission'=>fn()=>$sc()->canEditPages()],
            ['method'=>'PUT','path'=>'/sections/(?P<id>\d+)/style','callback'=>fn($r)=>$sc()->updateStyle($r),'permission'=>fn()=>$sc()->canEditPages()],
            ['method'=>'PUT','path'=>'/sections/(?P<id>\d+)/variant','callback'=>fn($r)=>$sc()->changeVariant($r),'permission'=>fn()=>$sc()->canEditPages()],
            ['method'=>'PUT','path'=>'/sections/(?P<id>\d+)/layout','callback'=>fn($r)=>$sc()->updateLayout($r),'permission'=>fn()=>$sc()->canEditPages()],
            ['method'=>'PUT','path'=>'/sections/(?P<id>\d+)/toggle','callback'=>fn($r)=>$sc()->toggle($r),'permission'=>fn()=>$sc()->canEditPages()],
            ['method'=>'PUT','path'=>'/sections/(?P<id>\d+)/move','callback'=>fn($r)=>$sc()->move($r),'permission'=>fn()=>$sc()->canEditPages()],
            ['method'=>'POST','path'=>'/sections/(?P<id>\d+)/duplicate','callback'=>fn($r)=>$sc()->duplicate($r),'permission'=>fn()=>$sc()->canEditPages()],
            ['method'=>'DELETE','path'=>'/sections/(?P<id>\d+)','callback'=>fn($r)=>$sc()->destroy($r),'permission'=>fn()=>$sc()->canEditPages()],
        ];
    }
}
