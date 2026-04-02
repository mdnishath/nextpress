<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\PageBuilder\Controller;

use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\PageRepository;
use NextPressBuilder\Core\Rest\AbstractController;
use NextPressBuilder\Modules\PageBuilder\Service\PageService;
use WP_REST_Request;
use WP_REST_Response;

class PageController extends AbstractController
{
    private PageService $service;
    private PageRepository $repo;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->service = $container->make(PageService::class);
        $this->repo = $container->make(PageRepository::class);
    }

    /** GET /npb/v1/pages — List pages (public: published only, admin: all). */
    public function list(WP_REST_Request $request): WP_REST_Response
    {
        $pageType = $request->get_param('type') ?? 'page';
        $status = $request->get_param('status');

        if (current_user_can('npb_edit_pages') && $status === 'all') {
            $pages = $this->repo->findByType($pageType);
        } else {
            $pages = $this->repo->findBy(['status' => 'published', 'page_type' => $pageType], 'title', 'ASC');
        }

        return $this->success($pages);
    }

    /** GET /npb/v1/pages/{slug} — Page with sections (public: published, admin: any). */
    public function get(WP_REST_Request $request): WP_REST_Response
    {
        $slug = $request->get_param('slug');
        $isAdmin = current_user_can('npb_edit_pages');

        $data = $this->service->getPageWithSections($slug, !$isAdmin);

        if (!$data) return $this->notFound('Page not found.');

        return $this->success($data);
    }

    /** POST /npb/v1/pages — Create page (admin). */
    public function store(WP_REST_Request $request): WP_REST_Response
    {
        $data = $this->getJsonBody($request);
        if (!$data) return $this->error('Invalid JSON body.');

        $missing = $this->checkRequired($data, ['title']);
        if ($missing) return $this->error('Missing: ' . implode(', ', $missing));

        $page = $this->service->create($data);
        return $this->created($page);
    }

    /** PUT /npb/v1/pages/{id} — Update page meta (admin). */
    public function update(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        if (!$this->repo->find($id)) return $this->notFound('Page not found.');

        $data = $this->getJsonBody($request);
        if (!$data) return $this->error('Invalid JSON body.');

        unset($data['id']);
        $this->repo->update($id, $data);
        return $this->success($this->repo->find($id));
    }

    /** PUT /npb/v1/pages/{id}/publish — Publish page (admin). */
    public function publish(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        if (!$this->service->publish($id)) return $this->notFound('Page not found.');
        return $this->success(['message' => 'Page published.', 'page' => $this->repo->find($id)]);
    }

    /** PUT /npb/v1/pages/{id}/unpublish — Unpublish page (admin). */
    public function unpublish(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $this->service->unpublish($id);
        return $this->success(['message' => 'Page unpublished.']);
    }

    /** POST /npb/v1/pages/{id}/duplicate — Duplicate page with sections (admin). */
    public function duplicate(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $newPage = $this->service->duplicate($id);
        if (!$newPage) return $this->notFound('Page not found.');
        return $this->created($newPage);
    }

    /** DELETE /npb/v1/pages/{id} — Delete page (admin). */
    public function destroy(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        if (!$this->service->delete($id)) return $this->notFound('Page not found.');
        return $this->success(['message' => 'Page deleted.']);
    }

    /** GET /npb/v1/pages/{id}/export — Export as JSON (admin). */
    public function export(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $data = $this->service->export($id);
        if (!$data) return $this->notFound('Page not found.');
        return $this->success($data);
    }

    /** POST /npb/v1/pages/import — Import from JSON (admin). */
    public function import(WP_REST_Request $request): WP_REST_Response
    {
        $data = $this->getJsonBody($request);
        if (!$data) return $this->error('Invalid JSON body.');
        $missing = $this->checkRequired($data, ['title']);
        if ($missing) return $this->error('Missing: ' . implode(', ', $missing));
        $page = $this->service->import($data);
        return $this->created($page);
    }
}
