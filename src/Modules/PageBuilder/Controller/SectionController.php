<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\PageBuilder\Controller;

use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\SectionRepository;
use NextPressBuilder\Core\Rest\AbstractController;
use NextPressBuilder\Modules\PageBuilder\Service\SectionService;
use WP_REST_Request;
use WP_REST_Response;

class SectionController extends AbstractController
{
    private SectionService $service;
    private SectionRepository $repo;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->service = $container->make(SectionService::class);
        $this->repo = $container->make(SectionRepository::class);
    }

    /** GET /npb/v1/pages/{id}/sections — Sections tree for a page. */
    public function listByPage(WP_REST_Request $request): WP_REST_Response
    {
        $pageId = (int) $request->get_param('id');
        $enabledOnly = !current_user_can('npb_edit_pages');
        $sections = $this->repo->findTreeByPage($pageId, $enabledOnly);
        return $this->success($sections);
    }

    /** POST /npb/v1/pages/{id}/sections — Add section to page. */
    public function store(WP_REST_Request $request): WP_REST_Response
    {
        $data = $this->getJsonBody($request) ?? [];
        $data['page_id'] = (int) $request->get_param('id');

        $result = $this->service->add($data);
        if (is_string($result)) return $this->error($result);

        return $this->created($result);
    }

    /** PUT /npb/v1/sections/{id} — Update full section. */
    public function update(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        if (!$this->repo->find($id)) return $this->notFound('Section not found.');

        $data = $this->getJsonBody($request) ?? [];
        unset($data['id'], $data['page_id']);
        $this->repo->update($id, $data);
        return $this->success($this->repo->find($id));
    }

    /** PUT /npb/v1/sections/{id}/content — Update content only. */
    public function updateContent(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $data = $this->getJsonBody($request) ?? [];
        $this->service->updateContent($id, $data);
        return $this->success($this->repo->find($id));
    }

    /** PUT /npb/v1/sections/{id}/style — Update style only. */
    public function updateStyle(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $data = $this->getJsonBody($request) ?? [];
        $this->service->updateStyle($id, $data);
        return $this->success($this->repo->find($id));
    }

    /** PUT /npb/v1/sections/{id}/variant — Change variant. */
    public function changeVariant(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $data = $this->getJsonBody($request) ?? [];
        $variant = $data['variant_id'] ?? '';
        if (!$variant) return $this->error('variant_id is required.');

        $result = $this->service->changeVariant($id, $variant);
        if (is_string($result)) return $this->error($result);

        return $this->success($result);
    }

    /** PUT /npb/v1/sections/{id}/layout — Update container layout. */
    public function updateLayout(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $data = $this->getJsonBody($request) ?? [];

        $result = $this->service->updateLayout($id, $data);
        if (is_string($result)) return $this->error($result);

        return $this->success($result);
    }

    /** PUT /npb/v1/sections/{id}/toggle — Enable/disable. */
    public function toggle(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $result = $this->service->toggle($id);
        if (is_string($result)) return $this->error($result);
        return $this->success($result);
    }

    /** PUT /npb/v1/sections/{id}/move — Move to different parent. */
    public function move(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $data = $this->getJsonBody($request) ?? [];
        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        $sortOrder = (int) ($data['sort_order'] ?? 0);

        $result = $this->service->move($id, $parentId ?: null, $sortOrder);
        if (is_string($result)) return $this->error($result);

        return $this->success($result);
    }

    /** POST /npb/v1/pages/{id}/sections/reorder — Reorder sections. */
    public function reorder(WP_REST_Request $request): WP_REST_Response
    {
        $data = $this->getJsonBody($request) ?? [];
        $orderedIds = $data['ids'] ?? [];
        if (empty($orderedIds)) return $this->error('ids array is required.');

        $this->service->reorder(array_map('intval', $orderedIds));
        return $this->success(['message' => 'Sections reordered.']);
    }

    /** POST /npb/v1/sections/{id}/duplicate — Duplicate section. */
    public function duplicate(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $newSection = $this->service->duplicate($id);
        if (!$newSection) return $this->notFound('Section not found.');
        return $this->created($newSection);
    }

    /** DELETE /npb/v1/sections/{id} — Delete section. */
    public function destroy(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        if (!$this->repo->find($id)) return $this->notFound('Section not found.');
        $this->repo->delete($id);
        return $this->success(['message' => 'Section deleted.']);
    }
}
