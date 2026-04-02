<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\NavigationManager\Controller;

use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\NavigationRepository;
use NextPressBuilder\Core\Rest\AbstractController;
use WP_REST_Request;
use WP_REST_Response;

class NavigationController extends AbstractController
{
    private NavigationRepository $repo;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->repo = $container->make(NavigationRepository::class);
    }

    /** GET /npb/v1/navigation — All menus (public). */
    public function list(WP_REST_Request $request): WP_REST_Response
    {
        $location = $request->get_param('location');
        $menus = $location
            ? $this->repo->findByLocation($location)
            : $this->repo->findBy([], 'name', 'ASC');
        return $this->success($menus);
    }

    /** GET /npb/v1/navigation/{slug} — Single menu (public). */
    public function get(WP_REST_Request $request): WP_REST_Response
    {
        $menu = $this->repo->findBySlug($request->get_param('slug'));
        return $menu ? $this->success($menu) : $this->notFound('Menu not found.');
    }

    /** POST /npb/v1/navigation — Create menu (admin). */
    public function store(WP_REST_Request $request): WP_REST_Response
    {
        $data = $this->getJsonBody($request);
        if (!$data) return $this->error('Invalid JSON body.');
        $missing = $this->checkRequired($data, ['name', 'slug', 'location', 'items']);
        if ($missing) return $this->error('Missing: ' . implode(', ', $missing));
        if ($this->repo->findBySlug($data['slug'])) return $this->error('Slug already exists.');
        $id = $this->repo->create($data);
        return $this->created($this->repo->find($id));
    }

    /** PUT /npb/v1/navigation/{id} — Update menu (admin). */
    public function update(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $menu = $this->repo->find($id);
        if (!$menu) return $this->notFound('Menu not found.');
        $data = $this->getJsonBody($request);
        if (!$data) return $this->error('Invalid JSON body.');
        unset($data['id'], $data['slug']);
        $this->repo->update($id, $data);
        return $this->success($this->repo->find($id));
    }

    /** DELETE /npb/v1/navigation/{id} — Delete menu (admin). */
    public function destroy(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        if (!$this->repo->find($id)) return $this->notFound('Menu not found.');
        $this->repo->delete($id);
        return $this->success(['message' => 'Menu deleted.']);
    }
}
