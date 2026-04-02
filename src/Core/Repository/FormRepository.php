<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Repository;

class FormRepository extends AbstractRepository
{
    protected function tableName(): string { return 'forms'; }

    protected function sanitizeRules(): array
    {
        return [
            'slug' => 'slug',
            'name' => 'text',
        ];
    }

    protected function jsonColumns(): array
    {
        return ['fields', 'settings', 'conditional_logic', 'multi_step', 'styling'];
    }
}
