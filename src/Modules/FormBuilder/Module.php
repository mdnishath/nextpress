<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\FormBuilder;

use NextPressBuilder\Core\AbstractModule;
use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\FormRepository;
use NextPressBuilder\Core\Repository\SubmissionRepository;
use NextPressBuilder\Modules\FormBuilder\Controller\FormController;
use NextPressBuilder\Modules\FormBuilder\Controller\SubmissionController;
use NextPressBuilder\Modules\FormBuilder\Service\FormService;
use NextPressBuilder\Modules\FormBuilder\Service\NotificationService;
use NextPressBuilder\Modules\FormBuilder\Service\SpamProtectionService;

class Module extends AbstractModule
{
    public function slug(): string { return 'form-builder'; }
    public function name(): string { return 'Form Builder'; }
    public function version(): string { return '1.0.0'; }

    public function register(Container $container): void
    {
        parent::register($container);

        $container->singleton(FormRepository::class, fn() => new FormRepository());
        $container->singleton(SubmissionRepository::class, fn() => new SubmissionRepository());
        $container->singleton(SpamProtectionService::class, fn() => new SpamProtectionService());
        $container->singleton(NotificationService::class, fn() => new NotificationService());

        $container->singleton(FormService::class, fn(Container $c) => new FormService(
            $c->make(FormRepository::class),
            $c->make(SubmissionRepository::class),
            $c->make(SpamProtectionService::class),
            $c->make(NotificationService::class),
        ));

        $container->singleton(FormController::class, fn(Container $c) => new FormController($c));
        $container->singleton(SubmissionController::class, fn(Container $c) => new SubmissionController($c));
    }

    public function boot(): void
    {
        $this->container->make(FormService::class)->seedDefaults();
    }

    public function routes(): array
    {
        $fc = fn() => $this->container->make(FormController::class);
        $sc = fn() => $this->container->make(SubmissionController::class);

        return [
            // Public.
            ['method'=>'GET','path'=>'/forms/(?P<slug>[a-z0-9_-]+)','callback'=>fn($r)=>$fc()->getBySlug($r),'permission'=>'__return_true'],
            ['method'=>'POST','path'=>'/forms/(?P<slug>[a-z0-9_-]+)/submit','callback'=>fn($r)=>$fc()->submit($r),'permission'=>'__return_true'],
            // Admin — forms.
            ['method'=>'GET','path'=>'/forms','callback'=>fn($r)=>$fc()->list($r),'permission'=>fn()=>$fc()->canManageForms()],
            ['method'=>'POST','path'=>'/forms','callback'=>fn($r)=>$fc()->store($r),'permission'=>fn()=>$fc()->canManageForms()],
            ['method'=>'PUT','path'=>'/forms/(?P<id>\d+)','callback'=>fn($r)=>$fc()->update($r),'permission'=>fn()=>$fc()->canManageForms()],
            ['method'=>'DELETE','path'=>'/forms/(?P<id>\d+)','callback'=>fn($r)=>$fc()->destroy($r),'permission'=>fn()=>$fc()->canManageForms()],
            // Admin — submissions.
            ['method'=>'GET','path'=>'/forms/(?P<id>\d+)/submissions','callback'=>fn($r)=>$sc()->list($r),'permission'=>fn()=>$fc()->canManageForms()],
            ['method'=>'GET','path'=>'/forms/(?P<id>\d+)/submissions/(?P<sid>\d+)','callback'=>fn($r)=>$sc()->get($r),'permission'=>fn()=>$fc()->canManageForms()],
            ['method'=>'PUT','path'=>'/forms/(?P<id>\d+)/submissions/(?P<sid>\d+)','callback'=>fn($r)=>$sc()->updateStatus($r),'permission'=>fn()=>$fc()->canManageForms()],
            ['method'=>'DELETE','path'=>'/forms/(?P<id>\d+)/submissions/(?P<sid>\d+)','callback'=>fn($r)=>$sc()->destroy($r),'permission'=>fn()=>$fc()->canManageForms()],
            ['method'=>'GET','path'=>'/forms/(?P<id>\d+)/submissions/export','callback'=>fn($r)=>$sc()->export($r),'permission'=>fn()=>$fc()->canManageForms()],
        ];
    }
}
