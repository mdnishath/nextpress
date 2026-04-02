<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Repository;

class ButtonRepository extends AbstractRepository
{
    protected function tableName(): string { return 'buttons'; }

    protected function sanitizeRules(): array
    {
        return [
            'slug' => 'slug',
            'name' => 'text',
        ];
    }

    protected function jsonColumns(): array
    {
        return ['preset'];
    }

    /** @return object[] */
    public function findDefaults(): array
    {
        return $this->findBy(['is_default' => 1], 'name', 'ASC');
    }
}
