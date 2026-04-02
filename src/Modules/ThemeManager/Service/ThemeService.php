<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\ThemeManager\Service;

use NextPressBuilder\Core\Repository\ThemeRepository;

/**
 * Business logic for theme management.
 *
 * Handles theme CRUD, preset seeding, import/export, and validation.
 */
class ThemeService
{
    public function __construct(
        private readonly ThemeRepository $repo,
        private readonly ColorUtility $colorUtil,
        private readonly CssVariableGenerator $cssGenerator,
    ) {}

    /**
     * Get the active theme.
     */
    public function getActive(): ?object
    {
        return $this->repo->findActive();
    }

    /**
     * Get CSS variables string for the active theme.
     */
    public function getCssVariables(): string
    {
        $theme = $this->getActive();
        if (!$theme) {
            return '/* No active theme */';
        }
        return $this->cssGenerator->generate($theme);
    }

    /**
     * Get font loading config for the active theme.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFontConfig(): array
    {
        $theme = $this->getActive();
        if (!$theme || !isset($theme->typography)) {
            return [];
        }
        return $this->cssGenerator->getFontConfig($theme->typography);
    }

    /**
     * Activate a theme by ID.
     */
    public function activate(int $id): bool
    {
        return $this->repo->activate($id);
    }

    /**
     * Create a new theme.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        return $this->repo->create($data);
    }

    /**
     * Update a theme.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        return $this->repo->update($id, $data);
    }

    /**
     * Delete a theme. Cannot delete the active theme.
     */
    public function delete(int $id): bool|string
    {
        $theme = $this->repo->find($id);
        if (!$theme) {
            return 'Theme not found.';
        }
        if (!empty($theme->is_active)) {
            return 'Cannot delete the active theme. Activate a different theme first.';
        }
        return $this->repo->delete($id);
    }

    /**
     * Export a theme as a JSON-compatible array.
     *
     * @return array<string, mixed>|null
     */
    public function export(int $id): ?array
    {
        $theme = $this->repo->find($id);
        if (!$theme) return null;

        $data = (array) $theme;
        unset($data['id'], $data['is_active'], $data['created_at'], $data['updated_at']);
        return $data;
    }

    /**
     * Import a theme from JSON data.
     *
     * @param array<string, mixed> $data
     */
    public function import(array $data): int
    {
        // Ensure unique slug.
        $slug = $data['slug'] ?? 'imported-' . time();
        if ($this->repo->findBySlug($slug)) {
            $slug .= '-' . time();
        }
        $data['slug'] = $slug;
        $data['is_active'] = 0;
        unset($data['id'], $data['created_at'], $data['updated_at']);

        return $this->repo->create($data);
    }

    /**
     * Seed all preset themes if none exist.
     */
    public function seedPresets(): void
    {
        if ($this->repo->count() > 0) {
            return;
        }

        $presets = $this->getPresetThemes();
        $first = true;

        foreach ($presets as $preset) {
            $preset['is_active'] = $first ? 1 : 0;
            $this->repo->create($preset);
            $first = false;
        }
    }

    /**
     * Get the 10 preset theme definitions.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getPresetThemes(): array
    {
        $typography = [
            'heading' => ['family' => 'Inter', 'source' => 'google', 'weights' => [600, 700, 800, 900], 'fallback' => 'system-ui, sans-serif'],
            'body'    => ['family' => 'Inter', 'source' => 'google', 'weights' => [300, 400, 500, 600, 700], 'fallback' => 'system-ui, sans-serif'],
            'mono'    => ['family' => 'JetBrains Mono', 'source' => 'google', 'weights' => [400, 500], 'fallback' => 'monospace'],
            'scale'   => [
                'xs' => ['size' => '12px', 'lineHeight' => '16px'],
                'sm' => ['size' => '14px', 'lineHeight' => '20px'],
                'base' => ['size' => '16px', 'lineHeight' => '24px'],
                'lg' => ['size' => '18px', 'lineHeight' => '28px'],
                'xl' => ['size' => '20px', 'lineHeight' => '28px'],
                '2xl' => ['size' => '24px', 'lineHeight' => '32px'],
                '3xl' => ['size' => '30px', 'lineHeight' => '36px'],
                '4xl' => ['size' => '36px', 'lineHeight' => '40px'],
                '5xl' => ['size' => '48px', 'lineHeight' => '1'],
                '6xl' => ['size' => '60px', 'lineHeight' => '1'],
                '7xl' => ['size' => '72px', 'lineHeight' => '1'],
            ],
        ];

        $spacing = [
            'base' => 4,
            'scale' => ['0'=>'0px','1'=>'4px','2'=>'8px','3'=>'12px','4'=>'16px','5'=>'20px','6'=>'24px','8'=>'32px','10'=>'40px','12'=>'48px','16'=>'64px','20'=>'80px','24'=>'96px','28'=>'112px','32'=>'128px'],
            'sectionPadding' => [
                'compact'  => ['mobile'=>'48px','tablet'=>'64px','desktop'=>'80px'],
                'default'  => ['mobile'=>'64px','tablet'=>'80px','desktop'=>'112px'],
                'spacious' => ['mobile'=>'80px','tablet'=>'112px','desktop'=>'144px'],
            ],
            'containerMaxWidth' => '1280px',
            'containerPadding' => ['mobile'=>'16px','tablet'=>'24px','desktop'=>'32px'],
        ];

        $borders = ['sm'=>'4px','md'=>'8px','lg'=>'12px','xl'=>'16px','full'=>'9999px'];
        $shadows = [
            'sm'  => '0 1px 2px rgba(0,0,0,0.05)',
            'md'  => '0 4px 6px rgba(0,0,0,0.07), 0 2px 4px rgba(0,0,0,0.06)',
            'lg'  => '0 10px 15px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05)',
            'xl'  => '0 20px 25px rgba(0,0,0,0.1), 0 8px 10px rgba(0,0,0,0.04)',
            '2xl' => '0 25px 50px rgba(0,0,0,0.15)',
        ];

        $makeColors = function(string $primary, string $accent, array $extra = []) {
            $cu = $this->colorUtil;
            return array_merge([
                'primary'       => ['value' => $primary, 'label' => 'Primary'],
                'primary-dark'  => ['value' => $cu->darken($primary, 15), 'label' => 'Primary Dark'],
                'primary-light' => ['value' => $cu->lighten($primary, 20), 'label' => 'Primary Light'],
                'accent'        => ['value' => $accent, 'label' => 'Accent'],
                'accent-dark'   => ['value' => $cu->darken($accent, 15), 'label' => 'Accent Dark'],
                'dark'          => ['value' => '#1A202C', 'label' => 'Dark'],
                'secondary'     => ['value' => '#2D3748', 'label' => 'Secondary'],
                'gray-50'  => '#F9FAFB', 'gray-100' => '#F3F4F6', 'gray-200' => '#E5E7EB',
                'gray-300' => '#D1D5DB', 'gray-400' => '#9CA3AF', 'gray-500' => '#6B7280',
                'gray-600' => '#4B5563', 'gray-700' => '#374151', 'gray-800' => '#1F2937', 'gray-900' => '#111827',
                'success' => '#10B981', 'warning' => '#F59E0B', 'error' => '#EF4444', 'info' => '#3B82F6',
                'white' => '#FFFFFF', 'black' => '#000000',
            ], $extra);
        };

        $makeDarkMode = function(string $primary) {
            $cu = $this->colorUtil;
            return [
                'enabled' => true,
                'toggle'  => 'system',
                'colors'  => [
                    'primary'       => $cu->lighten($primary, 25),
                    'primary-dark'  => $cu->lighten($primary, 15),
                    'primary-light' => $cu->lighten($primary, 35),
                    'dark'     => '#F9FAFB',
                    'secondary' => '#E5E7EB',
                    'gray-50'  => '#111827', 'gray-100' => '#1F2937', 'gray-200' => '#374151',
                    'gray-800' => '#F3F4F6', 'gray-900' => '#F9FAFB',
                    'white' => '#0F172A', 'black' => '#FFFFFF',
                ],
            ];
        };

        return [
            [
                'slug' => 'modern', 'name' => 'Modern',
                'colors' => $makeColors('#1E3A5F', '#D4942A'),
                'typography' => $typography,
                'spacing' => $spacing, 'buttons' => [], 'borders' => $borders, 'shadows' => $shadows,
                'dark_mode' => $makeDarkMode('#1E3A5F'),
            ],
            [
                'slug' => 'classic', 'name' => 'Classic',
                'colors' => $makeColors('#1B365D', '#C8915C'),
                'typography' => array_merge($typography, ['heading' => ['family'=>'Playfair Display','source'=>'google','weights'=>[600,700,800,900],'fallback'=>'Georgia, serif']]),
                'spacing' => $spacing, 'buttons' => [], 'borders' => $borders, 'shadows' => $shadows,
                'dark_mode' => $makeDarkMode('#1B365D'),
            ],
            [
                'slug' => 'bold', 'name' => 'Bold',
                'colors' => $makeColors('#111827', '#EF4444'),
                'typography' => array_merge($typography, ['heading' => ['family'=>'Space Grotesk','source'=>'google','weights'=>[600,700,800],'fallback'=>'system-ui, sans-serif']]),
                'spacing' => $spacing, 'buttons' => [], 'borders' => $borders, 'shadows' => $shadows,
                'dark_mode' => $makeDarkMode('#111827'),
            ],
            [
                'slug' => 'minimal', 'name' => 'Minimal',
                'colors' => $makeColors('#374151', '#6B7280'),
                'typography' => $typography,
                'spacing' => $spacing, 'buttons' => [], 'borders' => $borders, 'shadows' => $shadows,
                'dark_mode' => $makeDarkMode('#374151'),
            ],
            [
                'slug' => 'warm', 'name' => 'Warm',
                'colors' => $makeColors('#92400E', '#D97706'),
                'typography' => array_merge($typography, ['heading' => ['family'=>'DM Serif Display','source'=>'google','weights'=>[400],'fallback'=>'Georgia, serif']]),
                'spacing' => $spacing, 'buttons' => [], 'borders' => $borders, 'shadows' => $shadows,
                'dark_mode' => $makeDarkMode('#92400E'),
            ],
            [
                'slug' => 'cool', 'name' => 'Cool',
                'colors' => $makeColors('#1E40AF', '#3B82F6'),
                'typography' => $typography,
                'spacing' => $spacing, 'buttons' => [], 'borders' => $borders, 'shadows' => $shadows,
                'dark_mode' => $makeDarkMode('#1E40AF'),
            ],
            [
                'slug' => 'corporate', 'name' => 'Corporate',
                'colors' => $makeColors('#1F2937', '#059669'),
                'typography' => array_merge($typography, ['heading' => ['family'=>'Plus Jakarta Sans','source'=>'google','weights'=>[600,700,800],'fallback'=>'system-ui, sans-serif']]),
                'spacing' => $spacing, 'buttons' => [], 'borders' => $borders, 'shadows' => $shadows,
                'dark_mode' => $makeDarkMode('#1F2937'),
            ],
            [
                'slug' => 'creative', 'name' => 'Creative',
                'colors' => $makeColors('#7C3AED', '#EC4899'),
                'typography' => array_merge($typography, ['heading' => ['family'=>'Sora','source'=>'google','weights'=>[600,700,800],'fallback'=>'system-ui, sans-serif']]),
                'spacing' => $spacing, 'buttons' => [], 'borders' => $borders, 'shadows' => $shadows,
                'dark_mode' => $makeDarkMode('#7C3AED'),
            ],
            [
                'slug' => 'elegant', 'name' => 'Elegant',
                'colors' => $makeColors('#1C1917', '#A16207'),
                'typography' => array_merge($typography, ['heading' => ['family'=>'Cormorant Garamond','source'=>'google','weights'=>[600,700],'fallback'=>'Georgia, serif']]),
                'spacing' => $spacing, 'buttons' => [], 'borders' => $borders, 'shadows' => $shadows,
                'dark_mode' => $makeDarkMode('#1C1917'),
            ],
            [
                'slug' => 'playful', 'name' => 'Playful',
                'colors' => $makeColors('#DB2777', '#8B5CF6'),
                'typography' => array_merge($typography, ['heading' => ['family'=>'Nunito','source'=>'google','weights'=>[600,700,800,900],'fallback'=>'system-ui, sans-serif']]),
                'spacing' => $spacing, 'buttons' => [], 'borders' => array_merge($borders, ['md'=>'12px','lg'=>'16px','xl'=>'24px']), 'shadows' => $shadows,
                'dark_mode' => $makeDarkMode('#DB2777'),
            ],
        ];
    }
}
