<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\PageBuilder\Service;

use NextPressBuilder\Core\Repository\PageRepository;
use NextPressBuilder\Core\Repository\SectionRepository;
use NextPressBuilder\Core\WebhookManager;

/**
 * Business logic for page management.
 * Handles CRUD, publishing, duplication, import/export.
 */
class PageService
{
    public function __construct(
        private readonly PageRepository $pageRepo,
        private readonly SectionRepository $sectionRepo,
        private readonly WebhookManager $webhookManager,
    ) {}

    /**
     * Create a new page.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): object
    {
        // Ensure unique slug.
        $slug = $data['slug'] ?? sanitize_title($data['title'] ?? 'untitled');
        $counter = 0;
        $baseSlug = $slug;
        while ($this->pageRepo->slugExists($slug)) {
            $counter++;
            $slug = $baseSlug . '-' . $counter;
        }
        $data['slug'] = $slug;
        $data['status'] = $data['status'] ?? 'draft';
        $data['page_type'] = $data['page_type'] ?? 'page';

        $id = $this->pageRepo->create($data);
        return $this->pageRepo->find($id);
    }

    /**
     * Get a page with its section tree (for API response).
     *
     * @return array<string, mixed>|null
     */
    public function getPageWithSections(string $slug, bool $publishedOnly = true): ?array
    {
        $page = $this->pageRepo->findBySlug($slug);
        if (!$page) return null;

        if ($publishedOnly && ($page->status ?? '') !== 'published') {
            return null;
        }

        $sections = $this->sectionRepo->findTreeByPage((int) $page->id, $publishedOnly);

        $pageData = (array) $page;
        $pageData['sections'] = $sections;

        return $pageData;
    }

    /**
     * Publish a page and trigger revalidation.
     */
    public function publish(int $id): bool
    {
        $page = $this->pageRepo->find($id);
        if (!$page) return false;

        $this->pageRepo->update($id, ['status' => 'published']);

        // Trigger revalidation.
        $slug = $page->slug ?? '';
        $path = $slug === 'home' ? '/' : '/' . $slug;
        $this->webhookManager->triggerRevalidation([$path, '/'], 'page_published');

        return true;
    }

    /**
     * Unpublish (set to draft).
     */
    public function unpublish(int $id): bool
    {
        return $this->pageRepo->update($id, ['status' => 'draft']);
    }

    /**
     * Duplicate a page with all its sections.
     */
    public function duplicate(int $id): ?object
    {
        $page = $this->pageRepo->find($id);
        if (!$page) return null;

        // Duplicate the page record.
        $newPageId = $this->pageRepo->duplicate($id);
        if (!$newPageId) return null;

        // Duplicate all root sections and their children recursively.
        $sections = $this->sectionRepo->findByPage($id);
        $idMap = []; // old_id => new_id

        // First pass: create all sections.
        foreach ($sections as $section) {
            $oldId = (int) $section->id;
            $newId = $this->sectionRepo->duplicate($oldId, $newPageId);
            if ($newId) {
                $idMap[$oldId] = $newId;
            }
        }

        // Second pass: fix parent_id references.
        foreach ($sections as $section) {
            $oldId = (int) $section->id;
            $oldParent = isset($section->parent_id) ? (int) $section->parent_id : 0;

            if ($oldParent && isset($idMap[$oldId]) && isset($idMap[$oldParent])) {
                $this->sectionRepo->update($idMap[$oldId], ['parent_id' => $idMap[$oldParent]]);
            }
        }

        return $this->pageRepo->find($newPageId);
    }

    /**
     * Delete a page (cascades to sections via DB foreign key).
     */
    public function delete(int $id): bool
    {
        $page = $this->pageRepo->find($id);
        if (!$page) return false;

        // Delete sections first (in case no FK cascade).
        $this->sectionRepo->deleteByPage($id);

        return $this->pageRepo->delete($id);
    }

    /**
     * Export page as JSON.
     *
     * @return array<string, mixed>|null
     */
    public function export(int $id): ?array
    {
        $page = $this->pageRepo->find($id);
        if (!$page) return null;

        $sections = $this->sectionRepo->findByPage($id);
        $sectionData = array_map(function($s) {
            $d = (array) $s;
            unset($d['id'], $d['page_id'], $d['created_at'], $d['updated_at']);
            return $d;
        }, $sections);

        $pageData = (array) $page;
        unset($pageData['id'], $pageData['wp_post_id'], $pageData['created_at'], $pageData['updated_at']);
        $pageData['sections'] = $sectionData;

        return $pageData;
    }

    /**
     * Import page from JSON data.
     *
     * @param array<string, mixed> $data
     */
    public function import(array $data): object
    {
        $sections = $data['sections'] ?? [];
        unset($data['sections'], $data['id'], $data['wp_post_id'], $data['created_at'], $data['updated_at']);

        // Create page.
        $page = $this->create($data);
        $pageId = (int) $page->id;

        // Create sections.
        $parentMap = []; // temp_parent_ref => new_id

        foreach ($sections as $index => $sectionData) {
            $sectionData['page_id'] = $pageId;
            $sectionData['sort_order'] = $sectionData['sort_order'] ?? $index;

            // Handle parent_id mapping for nested sections.
            $origParent = $sectionData['parent_id'] ?? null;
            if ($origParent && isset($parentMap[$origParent])) {
                $sectionData['parent_id'] = $parentMap[$origParent];
            } else {
                $sectionData['parent_id'] = null;
            }

            unset($sectionData['id'], $sectionData['children']);
            $newId = $this->sectionRepo->create($sectionData);

            // Store mapping for child sections.
            $parentMap[$index] = $newId;
        }

        return $this->pageRepo->find($pageId);
    }
}
