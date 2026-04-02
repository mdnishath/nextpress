<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Repository;

class VariantRepository extends AbstractRepository
{
    protected function tableName(): string { return 'style_variants'; }

    protected function sanitizeRules(): array
    {
        return [
            'component_slug' => 'slug',
            'variant_slug'   => 'text',
            'name'           => 'text',
            'preview_image'  => 'url',
        ];
    }

    protected function jsonColumns(): array
    {
        return ['style'];
    }

    /** @return object[] */
    public function findByComponent(string $componentSlug): array
    {
        return $this->findBy(['component_slug' => $componentSlug], 'sort_order', 'ASC');
    }

    public function findVariant(string $componentSlug, string $variantSlug): ?object
    {
        return $this->findOne([
            'component_slug' => $componentSlug,
            'variant_slug'   => $variantSlug,
        ]);
    }

    public function countByComponent(string $componentSlug): int
    {
        return $this->count(['component_slug' => $componentSlug]);
    }
}
