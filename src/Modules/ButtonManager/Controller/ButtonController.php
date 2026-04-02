<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\ButtonManager\Controller;

use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\ButtonRepository;
use NextPressBuilder\Core\Rest\AbstractController;
use WP_REST_Request;
use WP_REST_Response;

class ButtonController extends AbstractController
{
    private ButtonRepository $repo;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->repo = $container->make(ButtonRepository::class);
    }

    /** GET /npb/v1/buttons — All presets (public). */
    public function list(WP_REST_Request $request): WP_REST_Response
    {
        return $this->success($this->repo->findBy([], 'name', 'ASC'));
    }

    /** GET /npb/v1/buttons/{slug} — Single preset (public). */
    public function get(WP_REST_Request $request): WP_REST_Response
    {
        $btn = $this->repo->findBySlug($request->get_param('slug'));
        return $btn ? $this->success($btn) : $this->notFound('Button preset not found.');
    }

    /** POST /npb/v1/buttons — Create preset (admin). */
    public function store(WP_REST_Request $request): WP_REST_Response
    {
        $data = $this->getJsonBody($request);
        if (!$data) return $this->error('Invalid JSON body.');
        $missing = $this->checkRequired($data, ['name', 'slug', 'preset']);
        if ($missing) return $this->error('Missing: ' . implode(', ', $missing));
        if ($this->repo->findBySlug($data['slug'])) return $this->error('Slug already exists.');
        $data['is_default'] = 0;
        $id = $this->repo->create($data);
        return $this->created($this->repo->find($id));
    }

    /** PUT /npb/v1/buttons/{id} — Update preset (admin). */
    public function update(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $btn = $this->repo->find($id);
        if (!$btn) return $this->notFound('Button preset not found.');
        $data = $this->getJsonBody($request);
        if (!$data) return $this->error('Invalid JSON body.');
        unset($data['id'], $data['slug'], $data['is_default']);
        $this->repo->update($id, $data);
        return $this->success($this->repo->find($id));
    }

    /** DELETE /npb/v1/buttons/{id} — Delete preset (admin). */
    public function destroy(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $btn = $this->repo->find($id);
        if (!$btn) return $this->notFound('Button preset not found.');
        if (!empty($btn->is_default)) return $this->error('Default presets cannot be deleted.');
        $this->repo->delete($id);
        return $this->success(['message' => 'Button preset deleted.']);
    }
}
