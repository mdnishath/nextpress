<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Repository;

class SectionRepository extends AbstractRepository
{
    protected function tableName(): string
    {
        return 'sections';
    }

    protected function sanitizeRules(): array
    {
        return [
            'section_type' => 'text',
            'variant_id'   => 'text',
            'animation'    => 'text',
            'custom_css'   => 'textarea',
            'custom_id'    => 'slug',
        ];
    }

    protected function jsonColumns(): array
    {
        return ['content', 'style', 'responsive', 'visibility', 'layout'];
    }

    /**
     * Get all sections for a page, ordered by sort_order.
     *
     * @return object[]
     */
    public function findByPage(int $pageId, bool $enabledOnly = false): array
    {
        $conditions = ['page_id' => $pageId];
        if ($enabledOnly) {
            $conditions['enabled'] = 1;
        }
        return $this->findBy($conditions, 'sort_order', 'ASC');
    }

    /**
     * Get sections as a nested tree (containers with children).
     *
     * @return object[]
     */
    public function findTreeByPage(int $pageId, bool $enabledOnly = false): array
    {
        $flat = $this->findByPage($pageId, $enabledOnly);
        return $this->buildTree($flat);
    }

    /**
     * Build a nested tree from flat section rows.
     *
     * @param object[] $sections
     * @return object[]
     */
    private function buildTree(array $sections, ?int $parentId = null): array
    {
        $tree = [];
        foreach ($sections as $section) {
            $sectionParent = isset($section->parent_id) ? (int) $section->parent_id : null;
            if ($sectionParent === $parentId || ($parentId === null && $sectionParent === 0)) {
                $section->children = $this->buildTree($sections, (int) $section->id);
                $tree[] = $section;
            }
        }
        return $tree;
    }

    /**
     * Get the next sort_order for a page (or within a parent container).
     */
    public function getNextSortOrder(int $pageId, ?int $parentId = null): int
    {
        $sql = "SELECT COALESCE(MAX(sort_order), -1) + 1 FROM {$this->table} WHERE page_id = %d";
        $values = [$pageId];

        if ($parentId !== null) {
            $sql .= " AND parent_id = %d";
            $values[] = $parentId;
        } else {
            $sql .= " AND (parent_id IS NULL OR parent_id = 0)";
        }

        return (int) $this->wpdb->get_var($this->wpdb->prepare($sql, ...$values));
    }

    /**
     * Reorder sections by providing an ordered array of section IDs.
     *
     * @param int[] $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            $this->wpdb->update(
                $this->table,
                ['sort_order' => $index],
                ['id' => $id],
                ['%d'],
                ['%d']
            );
        }
    }

    /**
     * Move a section to a different parent container.
     */
    public function moveToParent(int $sectionId, ?int $newParentId, int $sortOrder = 0): bool
    {
        return $this->update($sectionId, [
            'parent_id'  => $newParentId,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * Get nesting depth of a section.
     */
    public function getNestingDepth(int $sectionId): int
    {
        $depth = 0;
        $current = $this->find($sectionId);

        while ($current && !empty($current->parent_id)) {
            $depth++;
            $current = $this->find((int) $current->parent_id);
            if ($depth > 10) break; // Safety limit.
        }

        return $depth;
    }

    /**
     * Delete all sections for a page.
     */
    public function deleteByPage(int $pageId): bool
    {
        $result = $this->wpdb->delete($this->table, ['page_id' => $pageId], ['%d']);
        return $result !== false;
    }

    /**
     * Duplicate a section (optionally to a different page).
     */
    public function duplicate(int $sectionId, ?int $targetPageId = null): ?int
    {
        $section = $this->find($sectionId);
        if (!$section) return null;

        $data = (array) $section;
        unset($data['id'], $data['created_at'], $data['updated_at'], $data['children']);

        if ($targetPageId !== null) {
            $data['page_id'] = $targetPageId;
        }

        $data['sort_order'] = $this->getNextSortOrder(
            (int) $data['page_id'],
            isset($data['parent_id']) ? (int) $data['parent_id'] : null
        );

        return $this->create($data);
    }
}
