<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Repository;

class NavigationRepository extends AbstractRepository
{
    protected function tableName(): string { return 'navigation_menus'; }

    protected function sanitizeRules(): array
    {
        return [
            'slug'     => 'slug',
            'name'     => 'text',
            'location' => 'text',
        ];
    }

    protected function jsonColumns(): array
    {
        return ['items', 'settings'];
    }

    /** @return object[] */
    public function findByLocation(string $location): array
    {
        return $this->findBy(['location' => $location], 'name', 'ASC');
    }
}
