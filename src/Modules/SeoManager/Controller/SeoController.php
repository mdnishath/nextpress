<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\SeoManager\Controller;

use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\PageRepository;
use NextPressBuilder\Core\Rest\AbstractController;
use NextPressBuilder\Modules\SeoManager\Service\SeoService;
use WP_REST_Request;
use WP_REST_Response;

class SeoController extends AbstractController
{
    private SeoService $service;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->service = $container->make(SeoService::class);
    }

    /** GET /npb/v1/seo/global — Global SEO settings (public). */
    public function getGlobal(WP_REST_Request $request): WP_REST_Response
    {
        return $this->success($this->service->getGlobalSeo());
    }

    /** PUT /npb/v1/seo/global — Update global SEO (admin). */
    public function updateGlobal(WP_REST_Request $request): WP_REST_Response
    {
        $data = $this->getJsonBody($request);
        if (!$data) return $this->error('Invalid JSON body.');
        $this->service->updateGlobalSeo($data);
        return $this->success(['message' => 'Global SEO updated.']);
    }

    /** GET /npb/v1/seo/page/{slug} — Page SEO data (public). */
    public function getPageSeo(WP_REST_Request $request): WP_REST_Response
    {
        $slug = $request->get_param('slug');
        $pageRepo = $this->container->make(PageRepository::class);
        $page = $pageRepo->findBySlug($slug);

        if (!$page) return $this->notFound('Page not found.');

        return $this->success($this->service->buildPageSeo($page));
    }

    /** GET /npb/v1/seo/sitemap — Sitemap data for Next.js (public). */
    public function getSitemap(WP_REST_Request $request): WP_REST_Response
    {
        $pageRepo = $this->container->make(PageRepository::class);
        $pages = $pageRepo->findPublished();
        return $this->success(['urls' => $this->service->buildSitemap($pages)]);
    }

    /** GET /npb/v1/seo/redirects — All redirects (public, for Next.js middleware). */
    public function getRedirects(WP_REST_Request $request): WP_REST_Response
    {
        return $this->success($this->service->getRedirects());
    }

    /** POST /npb/v1/seo/redirects — Add redirect (admin). */
    public function addRedirect(WP_REST_Request $request): WP_REST_Response
    {
        $data = $this->getJsonBody($request);
        if (!$data) return $this->error('Invalid JSON body.');
        $missing = $this->checkRequired($data, ['from', 'to']);
        if ($missing) return $this->error('Missing: ' . implode(', ', $missing));
        $this->service->addRedirect($data['from'], $data['to'], (int) ($data['type'] ?? 301));
        return $this->created(['message' => 'Redirect added.']);
    }

    /** DELETE /npb/v1/seo/redirects/{index} — Remove redirect (admin). */
    public function removeRedirect(WP_REST_Request $request): WP_REST_Response
    {
        $index = (int) $request->get_param('index');
        $this->service->removeRedirect($index);
        return $this->success(['message' => 'Redirect removed.']);
    }
}
