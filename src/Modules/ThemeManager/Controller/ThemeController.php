<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\ThemeManager\Controller;

use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\ThemeRepository;
use NextPressBuilder\Core\Rest\AbstractController;
use NextPressBuilder\Modules\ThemeManager\Service\ThemeService;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API endpoints for theme management.
 */
class ThemeController extends AbstractController
{
    private ThemeService $service;
    private ThemeRepository $repo;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->service = $container->make(ThemeService::class);
        $this->repo = $container->make(ThemeRepository::class);
    }

    /**
     * GET /npb/v1/theme — Active theme (public).
     */
    public function getActive(WP_REST_Request $request): WP_REST_Response
    {
        $theme = $this->service->getActive();

        if (!$theme) {
            return $this->notFound('No active theme found.');
        }

        return $this->success($theme);
    }

    /**
     * GET /npb/v1/theme/css-variables — CSS custom properties (public).
     */
    public function getCssVariables(WP_REST_Request $request): WP_REST_Response
    {
        $css = $this->service->getCssVariables();

        $response = new WP_REST_Response($css, 200);
        $response->header('Content-Type', 'text/css; charset=UTF-8');
        return $response;
    }

    /**
     * GET /npb/v1/theme/fonts — Font loading config (public).
     */
    public function getFonts(WP_REST_Request $request): WP_REST_Response
    {
        return $this->success($this->service->getFontConfig());
    }

    /**
     * GET /npb/v1/themes — List all themes (admin).
     */
    public function list(WP_REST_Request $request): WP_REST_Response
    {
        $themes = $this->repo->findBy([], 'name', 'ASC');
        return $this->success($themes);
    }

    /**
     * GET /npb/v1/themes/{id} — Get single theme (admin).
     */
    public function get(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $theme = $this->repo->find($id);

        if (!$theme) {
            return $this->notFound('Theme not found.');
        }

        return $this->success($theme);
    }

    /**
     * POST /npb/v1/themes — Create theme (admin).
     */
    public function store(WP_REST_Request $request): WP_REST_Response
    {
        $data = $this->getJsonBody($request);
        if (!$data) {
            return $this->error('Invalid JSON body.');
        }

        $missing = $this->checkRequired($data, ['name', 'slug', 'colors', 'typography', 'spacing']);
        if ($missing) {
            return $this->error('Missing required fields: ' . implode(', ', $missing));
        }

        if ($this->repo->findBySlug($data['slug'])) {
            return $this->error('A theme with this slug already exists.');
        }

        $data['is_active'] = 0;
        $id = $this->service->create($data);

        return $this->created($this->repo->find($id));
    }

    /**
     * PUT /npb/v1/themes/{id} — Update theme (admin).
     */
    public function update(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $theme = $this->repo->find($id);

        if (!$theme) {
            return $this->notFound('Theme not found.');
        }

        $data = $this->getJsonBody($request);
        if (!$data) {
            return $this->error('Invalid JSON body.');
        }

        // Don't allow changing slug or active status via update.
        unset($data['slug'], $data['is_active'], $data['id']);

        $this->service->update($id, $data);

        return $this->success($this->repo->find($id));
    }

    /**
     * PUT /npb/v1/themes/{id}/activate — Activate theme (admin).
     */
    public function activate(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $theme = $this->repo->find($id);

        if (!$theme) {
            return $this->notFound('Theme not found.');
        }

        $this->service->activate($id);

        return $this->success([
            'message'      => 'Theme activated successfully.',
            'active_theme' => $this->repo->find($id),
        ]);
    }

    /**
     * DELETE /npb/v1/themes/{id} — Delete theme (admin).
     */
    public function destroy(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $result = $this->service->delete($id);

        if (is_string($result)) {
            return $this->error($result);
        }

        return $this->success(['message' => 'Theme deleted.']);
    }

    /**
     * GET /npb/v1/themes/{id}/export — Export theme as JSON (admin).
     */
    public function export(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $data = $this->service->export($id);

        if (!$data) {
            return $this->notFound('Theme not found.');
        }

        return $this->success($data);
    }

    /**
     * POST /npb/v1/themes/import — Import theme from JSON (admin).
     */
    public function import(WP_REST_Request $request): WP_REST_Response
    {
        $data = $this->getJsonBody($request);
        if (!$data) {
            return $this->error('Invalid JSON body.');
        }

        $missing = $this->checkRequired($data, ['name', 'colors', 'typography', 'spacing']);
        if ($missing) {
            return $this->error('Missing required fields: ' . implode(', ', $missing));
        }

        $id = $this->service->import($data);
        return $this->created($this->repo->find($id));
    }
}
