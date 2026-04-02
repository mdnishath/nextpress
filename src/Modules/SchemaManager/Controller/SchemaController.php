<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\SchemaManager\Controller;

use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\PageRepository;
use NextPressBuilder\Core\Repository\SectionRepository;
use NextPressBuilder\Core\Rest\AbstractController;
use NextPressBuilder\Modules\SchemaManager\Service\SchemaService;
use WP_REST_Request;
use WP_REST_Response;

class SchemaController extends AbstractController
{
    private SchemaService $service;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->service = $container->make(SchemaService::class);
    }

    /** GET /npb/v1/schema/global — Organization schema (public). */
    public function getGlobal(WP_REST_Request $request): WP_REST_Response
    {
        return $this->success($this->service->getGlobalSchema());
    }

    /** GET /npb/v1/schema/page/{slug} — Page JSON-LD (public). */
    public function getPageSchema(WP_REST_Request $request): WP_REST_Response
    {
        $slug = $request->get_param('slug');
        $pageRepo = $this->container->make(PageRepository::class);
        $page = $pageRepo->findBySlug($slug);
        if (!$page) return $this->notFound('Page not found.');

        $sectionRepo = $this->container->make(SectionRepository::class);
        $sections = $sectionRepo->findByPage((int) $page->id, true);

        return $this->success($this->service->buildPageSchema($page, $sections));
    }

    /** GET /npb/v1/schema/types — Supported business types (public). */
    public function getTypes(WP_REST_Request $request): WP_REST_Response
    {
        return $this->success(SchemaService::supportedTypes());
    }
}
