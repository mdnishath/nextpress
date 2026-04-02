<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Repository;

class TemplateRepository extends AbstractRepository
{
    protected function tableName(): string { return 'templates'; }

    protected function sanitizeRules(): array
    {
        return [
            'slug'          => 'slug',
            'name'          => 'text',
            'business_type' => 'text',
            'description'   => 'textarea',
            'preview_image' => 'url',
            'version'       => 'text',
        ];
    }

    protected function jsonColumns(): array
    {
        return ['data'];
    }

    /** @return object[] */
    public function findByBusinessType(string $type): array
    {
        return $this->findBy(['business_type' => $type], 'name', 'ASC');
    }

    /** @return object[] */
    public function findFree(): array
    {
        return $this->findBy(['is_premium' => 0], 'name', 'ASC');
    }
}
