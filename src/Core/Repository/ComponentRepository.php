<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Repository;

class ComponentRepository extends AbstractRepository
{
    protected function tableName(): string { return 'components'; }

    protected function sanitizeRules(): array
    {
        return [
            'slug'        => 'slug',
            'name'        => 'text',
            'category'    => 'text',
            'description' => 'textarea',
            'preview_image' => 'url',
        ];
    }

    protected function jsonColumns(): array
    {
        return ['content_schema', 'default_content', 'default_style'];
    }

    /** @return object[] */
    public function findByCategory(string $category): array
    {
        return $this->findBy(['category' => $category], 'name', 'ASC');
    }

    /** @return string[] */
    public function getCategories(): array
    {
        $rows = $this->wpdb->get_col(
            "SELECT DISTINCT category FROM {$this->table} ORDER BY category ASC"
        );
        return $rows ?: [];
    }

    /** @return object[] */
    public function findBuiltIn(): array
    {
        return $this->findBy(['is_user_created' => 0], 'category', 'ASC');
    }

    /** @return object[] */
    public function findUserCreated(): array
    {
        return $this->findBy(['is_user_created' => 1], 'name', 'ASC');
    }
}
