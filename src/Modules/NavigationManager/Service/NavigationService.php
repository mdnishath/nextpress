<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\NavigationManager\Service;

use NextPressBuilder\Core\Repository\NavigationRepository;

/**
 * Manages navigation menus with nested items.
 *
 * Menus can be assigned to locations: header, footer, sidebar, custom.
 * Items support: page links, custom URLs, anchors, phone, email, dropdown groups.
 */
class NavigationService
{
    public function __construct(
        private readonly NavigationRepository $repo,
    ) {}

    /**
     * Seed default menus if none exist.
     */
    public function seedDefaults(): void
    {
        if ($this->repo->count() > 0) {
            return;
        }

        $this->repo->create([
            'slug'     => 'main',
            'name'     => 'Main Navigation',
            'location' => 'header',
            'items'    => [
                $this->makeItem('Home', '/', 'page_link'),
                $this->makeItem('Services', '/services', 'page_link', [
                    $this->makeItem('Service One', '/services/one', 'page_link'),
                    $this->makeItem('Service Two', '/services/two', 'page_link'),
                ]),
                $this->makeItem('About', '/about', 'page_link'),
                $this->makeItem('Contact', '/contact', 'page_link'),
            ],
            'settings' => ['sticky' => true],
        ]);

        $this->repo->create([
            'slug'     => 'footer',
            'name'     => 'Footer Navigation',
            'location' => 'footer',
            'items'    => [
                $this->makeItem('Home', '/', 'page_link'),
                $this->makeItem('Services', '/services', 'page_link'),
                $this->makeItem('About', '/about', 'page_link'),
                $this->makeItem('Contact', '/contact', 'page_link'),
                $this->makeItem('Privacy Policy', '/privacy', 'page_link'),
            ],
            'settings' => [],
        ]);
    }

    /**
     * Helper to create a menu item structure.
     *
     * @param array<int, array<string, mixed>> $children
     * @return array<string, mixed>
     */
    private function makeItem(string $label, string $url, string $type = 'page_link', array $children = []): array
    {
        return [
            'id'       => 'item-' . wp_generate_password(8, false),
            'type'     => $type,
            'label'    => $label,
            'url'      => $url,
            'target'   => '_self',
            'icon'     => '',
            'badge'    => '',
            'nofollow' => false,
            'cssClass' => '',
            'children' => $children,
        ];
    }
}
