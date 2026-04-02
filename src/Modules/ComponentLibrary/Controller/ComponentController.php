<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\ComponentLibrary\Controller;

use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\ComponentRepository;
use NextPressBuilder\Core\Repository\VariantRepository;
use NextPressBuilder\Core\Rest\AbstractController;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API endpoints for components and variants.
 */
class ComponentController extends AbstractController
{
    private ComponentRepository $componentRepo;
    private VariantRepository $variantRepo;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->componentRepo = $container->make(ComponentRepository::class);
        $this->variantRepo = $container->make(VariantRepository::class);
    }

    /**
     * GET /npb/v1/components — List all components with categories (public).
     */
    public function list(WP_REST_Request $request): WP_REST_Response
    {
        $category = $request->get_param('category');

        if ($category) {
            $components = $this->componentRepo->findByCategory($category);
        } else {
            $components = $this->componentRepo->findBy([], 'category', 'ASC');
        }

        // Group by category.
        $grouped = [];
        foreach ($components as $comp) {
            $cat = $comp->category ?? 'other';
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][] = $comp;
        }

        return $this->success([
            'components' => $components,
            'grouped'    => $grouped,
            'categories' => $this->componentRepo->getCategories(),
            'total'      => count($components),
        ]);
    }

    /**
     * GET /npb/v1/components/{slug} — Single component + schema (public).
     */
    public function get(WP_REST_Request $request): WP_REST_Response
    {
        $slug = $request->get_param('slug');
        $component = $this->componentRepo->findBySlug($slug);

        if (!$component) {
            return $this->notFound('Component not found.');
        }

        return $this->success($component);
    }

    /**
     * GET /npb/v1/components/{slug}/variants — List variants for a component (public).
     */
    public function getVariants(WP_REST_Request $request): WP_REST_Response
    {
        $slug = $request->get_param('slug');

        // Verify component exists.
        $component = $this->componentRepo->findBySlug($slug);
        if (!$component) {
            return $this->notFound('Component not found.');
        }

        $variants = $this->variantRepo->findByComponent($slug);

        return $this->success([
            'component' => $slug,
            'variants'  => $variants,
            'total'     => count($variants),
        ]);
    }

    /**
     * POST /npb/v1/components — Create user component (admin).
     */
    public function store(WP_REST_Request $request): WP_REST_Response
    {
        $data = $this->getJsonBody($request);
        if (!$data) return $this->error('Invalid JSON body.');

        $missing = $this->checkRequired($data, ['name', 'slug', 'category', 'content_schema']);
        if ($missing) return $this->error('Missing: ' . implode(', ', $missing));

        if ($this->componentRepo->findBySlug($data['slug'])) {
            return $this->error('A component with this slug already exists.');
        }

        $data['is_user_created'] = 1;
        $id = $this->componentRepo->create($data);

        return $this->created($this->componentRepo->find($id));
    }

    /**
     * PUT /npb/v1/components/{id} — Update component (admin).
     */
    public function update(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $component = $this->componentRepo->find($id);

        if (!$component) return $this->notFound('Component not found.');

        $data = $this->getJsonBody($request);
        if (!$data) return $this->error('Invalid JSON body.');

        unset($data['id'], $data['slug'], $data['is_user_created']);
        $this->componentRepo->update($id, $data);

        return $this->success($this->componentRepo->find($id));
    }

    /**
     * POST /npb/v1/components/{id}/toggle — Toggle active state.
     */
    public function toggle(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $component = $this->componentRepo->find($id);

        if (!$component) return $this->notFound('Component not found.');

        $currentActive = (int) ($component->is_active ?? 1);
        $this->componentRepo->update($id, ['is_active' => $currentActive ? 0 : 1]);

        $updated = $this->componentRepo->find($id);
        return $this->success($updated);
    }

    /**
     * DELETE /npb/v1/components/{id} — Delete user component (admin).
     */
    public function destroy(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $component = $this->componentRepo->find($id);

        if (!$component) return $this->notFound('Component not found.');

        // Guard removed temporarily — rebuilding from scratch
        // if (empty($component->is_user_created)) {
        //     return $this->error('Built-in components cannot be deleted.');
        // }

        $this->componentRepo->delete($id);
        return $this->success(['message' => 'Component deleted.']);
    }
}
