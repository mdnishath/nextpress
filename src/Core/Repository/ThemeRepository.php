<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Repository;

class ThemeRepository extends AbstractRepository
{
    protected function tableName(): string { return 'themes'; }

    protected function sanitizeRules(): array
    {
        return [
            'slug'       => 'slug',
            'name'       => 'text',
            'custom_css' => 'textarea',
        ];
    }

    protected function jsonColumns(): array
    {
        return ['colors', 'typography', 'spacing', 'buttons', 'borders', 'shadows', 'dark_mode'];
    }

    public function findActive(): ?object
    {
        return $this->findOne(['is_active' => 1]);
    }

    public function activate(int $id): bool
    {
        // Deactivate all first.
        $this->wpdb->update($this->table, ['is_active' => 0], ['is_active' => 1]);
        // Activate the chosen one.
        return $this->update($id, ['is_active' => 1]);
    }
}
