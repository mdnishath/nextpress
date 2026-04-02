<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\PageBuilder\Service;

use NextPressBuilder\Core\Repository\ComponentRepository;
use NextPressBuilder\Core\Repository\SectionRepository;
use NextPressBuilder\Core\Repository\VariantRepository;

/**
 * Business logic for section management within pages.
 * Handles add, edit, reorder, nest, duplicate, variant switching.
 */
class SectionService
{
    private const MAX_NESTING_DEPTH = 5;

    public function __construct(
        private readonly SectionRepository $sectionRepo,
        private readonly ComponentRepository $componentRepo,
        private readonly VariantRepository $variantRepo,
    ) {}

    /**
     * Add a new section to a page.
     *
     * @param array<string, mixed> $data Must include page_id and section_type.
     * @return object|string The created section or error message.
     */
    public function add(array $data): object|string
    {
        $pageId = (int) ($data['page_id'] ?? 0);
        $sectionType = $data['section_type'] ?? '';

        if (!$pageId || !$sectionType) {
            return 'page_id and section_type are required.';
        }

        // Validate section type exists (built-in types bypass DB check).
        $builtinTypes = ['container', 'grid', 'heading'];
        if (!in_array($sectionType, $builtinTypes, true)) {
            $component = $this->componentRepo->findBySlug($sectionType);
            if (!$component) {
                return "Unknown section type: {$sectionType}";
            }
        }

        // Check nesting depth.
        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        if ($parentId) {
            $depth = $this->sectionRepo->getNestingDepth($parentId);
            if ($depth >= self::MAX_NESTING_DEPTH) {
                return 'Maximum nesting depth (' . self::MAX_NESTING_DEPTH . ') reached.';
            }

            // Verify parent is a container.
            $parent = $this->sectionRepo->find($parentId);
            if (!$parent || ($parent->section_type ?? '') !== 'container') {
                return 'Parent must be a container section.';
            }
        }

        // Set defaults.
        $data['sort_order'] = $data['sort_order'] ?? $this->sectionRepo->getNextSortOrder($pageId, $parentId);
        $data['enabled'] = $data['enabled'] ?? 1;
        $data['variant_id'] = $data['variant_id'] ?? 'variant-01';

        // Get default content from component.
        if ($sectionType !== 'container' && empty($data['content'])) {
            $component = $this->componentRepo->findBySlug($sectionType);
            if ($component) {
                $defaultContent = $component->default_content ?? null;
                $data['content'] = $defaultContent ? (is_object($defaultContent) ? (array) $defaultContent : $defaultContent) : [];
                $data['style'] = isset($component->default_style) ? (is_object($component->default_style) ? (array) $component->default_style : $component->default_style) : [];
            }
        }

        // Container defaults.
        if ($sectionType === 'container' && empty($data['layout'])) {
            $data['layout'] = [
                'type' => 'flex',
                'direction' => 'row',
                'wrap' => 'nowrap',
                'justifyContent' => 'flex-start',
                'alignItems' => 'stretch',
                'gap' => ['row' => '24px', 'column' => '24px'],
                'padding' => ['top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0'],
                'width' => '100%',
                'maxWidth' => '1280px',
            ];
            $data['content'] = $data['content'] ?? [];
        }

        $id = $this->sectionRepo->create($data);
        return $this->sectionRepo->find($id);
    }

    /**
     * Update section content.
     *
     * @param array<string, mixed> $content
     */
    public function updateContent(int $sectionId, array $content): bool
    {
        return $this->sectionRepo->update($sectionId, ['content' => $content]);
    }

    /**
     * Update section style.
     *
     * @param array<string, mixed> $style
     */
    public function updateStyle(int $sectionId, array $style): bool
    {
        return $this->sectionRepo->update($sectionId, ['style' => $style]);
    }

    /**
     * Update section layout (for containers only).
     *
     * @param array<string, mixed> $layout
     */
    public function updateLayout(int $sectionId, array $layout): object|string
    {
        $section = $this->sectionRepo->find($sectionId);
        if (!$section) return 'Section not found.';

        if (($section->section_type ?? '') !== 'container') {
            return 'Layout can only be set on container sections.';
        }

        $this->sectionRepo->update($sectionId, ['layout' => $layout]);
        return $this->sectionRepo->find($sectionId);
    }

    /**
     * Change the style variant.
     */
    public function changeVariant(int $sectionId, string $variantSlug): object|string
    {
        $section = $this->sectionRepo->find($sectionId);
        if (!$section) return 'Section not found.';

        // Validate variant exists.
        $sectionType = $section->section_type ?? '';
        if ($sectionType !== 'container') {
            $variant = $this->variantRepo->findVariant($sectionType, $variantSlug);
            if (!$variant) {
                return "Variant '{$variantSlug}' not found for component '{$sectionType}'.";
            }
        }

        $this->sectionRepo->update($sectionId, ['variant_id' => $variantSlug]);
        return $this->sectionRepo->find($sectionId);
    }

    /**
     * Reorder sections within a page (or within a parent container).
     *
     * @param int[] $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        $this->sectionRepo->reorder($orderedIds);
    }

    /**
     * Move a section to a different parent (or to root).
     */
    public function move(int $sectionId, ?int $newParentId, int $sortOrder = 0): object|string
    {
        if ($newParentId !== null) {
            $depth = $this->sectionRepo->getNestingDepth($newParentId);
            if ($depth >= self::MAX_NESTING_DEPTH) {
                return 'Maximum nesting depth reached.';
            }

            $parent = $this->sectionRepo->find($newParentId);
            if (!$parent || ($parent->section_type ?? '') !== 'container') {
                return 'Target parent must be a container.';
            }
        }

        $this->sectionRepo->moveToParent($sectionId, $newParentId, $sortOrder);
        return $this->sectionRepo->find($sectionId);
    }

    /**
     * Toggle section enabled/disabled.
     */
    public function toggle(int $sectionId): object|string
    {
        $section = $this->sectionRepo->find($sectionId);
        if (!$section) return 'Section not found.';

        $enabled = empty($section->enabled) ? 1 : 0;
        $this->sectionRepo->update($sectionId, ['enabled' => $enabled]);
        return $this->sectionRepo->find($sectionId);
    }

    /**
     * Duplicate a section (stays in same page).
     */
    public function duplicate(int $sectionId): ?object
    {
        $newId = $this->sectionRepo->duplicate($sectionId);
        return $newId ? $this->sectionRepo->find($newId) : null;
    }
}
