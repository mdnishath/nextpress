<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Repository;

class PageRepository extends AbstractRepository
{
    protected function tableName(): string
    {
        return 'pages';
    }

    protected function sanitizeRules(): array
    {
        return [
            'slug'            => 'slug',
            'title'           => 'text',
            'status'          => 'text',
            'page_type'       => 'text',
            'seo_title'       => 'text',
            'seo_description' => 'textarea',
            'seo_keywords'    => 'text',
            'og_image'        => 'url',
            'schema_type'     => 'text',
            'template_id'     => 'text',
        ];
    }

    protected function jsonColumns(): array
    {
        return ['schema_data', 'settings'];
    }

    /**
     * Find all published pages.
     *
     * @return object[]
     */
    public function findPublished(string $orderBy = 'title', string $order = 'ASC'): array
    {
        return $this->findBy(['status' => 'published'], $orderBy, $order);
    }

    /**
     * Find pages by type (page, header, footer, component).
     *
     * @return object[]
     */
    public function findByType(string $type): array
    {
        return $this->findBy(['page_type' => $type], 'title', 'ASC');
    }

    /**
     * Find published headers.
     *
     * @return object[]
     */
    public function findHeaders(): array
    {
        return $this->findBy(['page_type' => 'header', 'status' => 'published']);
    }

    /**
     * Find published footers.
     *
     * @return object[]
     */
    public function findFooters(): array
    {
        return $this->findBy(['page_type' => 'footer', 'status' => 'published']);
    }

    /**
     * Duplicate a page (without its sections).
     */
    public function duplicate(int $id): ?int
    {
        $page = $this->find($id);
        if (!$page) {
            return null;
        }

        $data = (array) $page;
        unset($data['id'], $data['created_at'], $data['updated_at']);
        $data['slug'] = $data['slug'] . '-copy-' . time();
        $data['title'] = $data['title'] . ' (Copy)';
        $data['status'] = 'draft';

        return $this->create($data);
    }

    /**
     * Check if a slug is unique.
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE slug = %s";
        $values = [$slug];

        if ($excludeId !== null) {
            $sql .= " AND id != %d";
            $values[] = $excludeId;
        }

        return (int) $this->wpdb->get_var($this->wpdb->prepare($sql, ...$values)) > 0;
    }
}
