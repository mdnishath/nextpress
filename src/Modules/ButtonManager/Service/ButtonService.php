<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\ButtonManager\Service;

use NextPressBuilder\Core\Repository\ButtonRepository;

/**
 * Manages reusable button style presets.
 *
 * Button presets define the visual appearance of all CTA buttons
 * across the site. Sections reference presets by slug.
 */
class ButtonService
{
    public function __construct(
        private readonly ButtonRepository $repo,
    ) {}

    /**
     * Seed 10 default button presets if none exist.
     */
    public function seedDefaults(): void
    {
        if ($this->repo->count() > 0) {
            return;
        }

        foreach ($this->getDefaultPresets() as $preset) {
            $this->repo->create($preset);
        }
    }

    /**
     * 10 built-in button preset definitions.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getDefaultPresets(): array
    {
        return [
            [
                'slug' => 'primary-solid',
                'name' => 'Primary Solid',
                'is_default' => 1,
                'preset' => [
                    'bg' => 'var(--color-primary)',
                    'color' => '#FFFFFF',
                    'hoverBg' => 'var(--color-primary-dark)',
                    'hoverColor' => '#FFFFFF',
                    'radius' => 'var(--radius-md)',
                    'padding' => '12px 28px',
                    'fontSize' => '15px',
                    'fontWeight' => '600',
                    'fontFamily' => 'var(--font-heading)',
                    'shadow' => 'var(--shadow-sm)',
                    'border' => 'none',
                    'transition' => 'all 0.2s ease',
                    'textTransform' => 'none',
                    'letterSpacing' => '0',
                ],
            ],
            [
                'slug' => 'secondary-solid',
                'name' => 'Secondary Solid',
                'is_default' => 1,
                'preset' => [
                    'bg' => 'var(--color-secondary)',
                    'color' => '#FFFFFF',
                    'hoverBg' => 'var(--color-dark)',
                    'hoverColor' => '#FFFFFF',
                    'radius' => 'var(--radius-md)',
                    'padding' => '12px 28px',
                    'fontSize' => '15px',
                    'fontWeight' => '600',
                    'fontFamily' => 'var(--font-heading)',
                    'shadow' => 'none',
                    'border' => 'none',
                    'transition' => 'all 0.2s ease',
                ],
            ],
            [
                'slug' => 'accent-solid',
                'name' => 'Accent Solid',
                'is_default' => 1,
                'preset' => [
                    'bg' => 'var(--color-accent)',
                    'color' => '#FFFFFF',
                    'hoverBg' => 'var(--color-accent-dark)',
                    'hoverColor' => '#FFFFFF',
                    'radius' => 'var(--radius-md)',
                    'padding' => '12px 28px',
                    'fontSize' => '15px',
                    'fontWeight' => '600',
                    'shadow' => 'var(--shadow-sm)',
                    'border' => 'none',
                    'transition' => 'all 0.2s ease',
                ],
            ],
            [
                'slug' => 'outline',
                'name' => 'Outline',
                'is_default' => 1,
                'preset' => [
                    'bg' => 'transparent',
                    'color' => 'var(--color-primary)',
                    'hoverBg' => 'var(--color-primary)',
                    'hoverColor' => '#FFFFFF',
                    'radius' => 'var(--radius-md)',
                    'padding' => '11px 27px',
                    'fontSize' => '15px',
                    'fontWeight' => '600',
                    'shadow' => 'none',
                    'border' => '2px solid var(--color-primary)',
                    'transition' => 'all 0.2s ease',
                ],
            ],
            [
                'slug' => 'ghost',
                'name' => 'Ghost',
                'is_default' => 1,
                'preset' => [
                    'bg' => 'transparent',
                    'color' => 'var(--color-primary)',
                    'hoverBg' => 'rgba(0,0,0,0.05)',
                    'hoverColor' => 'var(--color-primary-dark)',
                    'radius' => 'var(--radius-md)',
                    'padding' => '12px 28px',
                    'fontSize' => '15px',
                    'fontWeight' => '600',
                    'shadow' => 'none',
                    'border' => 'none',
                    'transition' => 'all 0.2s ease',
                ],
            ],
            [
                'slug' => 'pill',
                'name' => 'Pill',
                'is_default' => 1,
                'preset' => [
                    'bg' => 'var(--color-primary)',
                    'color' => '#FFFFFF',
                    'hoverBg' => 'var(--color-primary-dark)',
                    'hoverColor' => '#FFFFFF',
                    'radius' => 'var(--radius-full)',
                    'padding' => '12px 32px',
                    'fontSize' => '15px',
                    'fontWeight' => '600',
                    'shadow' => 'var(--shadow-md)',
                    'border' => 'none',
                    'transition' => 'all 0.2s ease',
                ],
            ],
            [
                'slug' => 'sharp',
                'name' => 'Sharp',
                'is_default' => 1,
                'preset' => [
                    'bg' => 'var(--color-dark)',
                    'color' => '#FFFFFF',
                    'hoverBg' => 'var(--color-primary)',
                    'hoverColor' => '#FFFFFF',
                    'radius' => '0',
                    'padding' => '14px 32px',
                    'fontSize' => '14px',
                    'fontWeight' => '700',
                    'shadow' => 'none',
                    'border' => 'none',
                    'transition' => 'all 0.2s ease',
                    'textTransform' => 'uppercase',
                    'letterSpacing' => '0.05em',
                ],
            ],
            [
                'slug' => 'gradient',
                'name' => 'Gradient',
                'is_default' => 1,
                'preset' => [
                    'bg' => 'linear-gradient(135deg, var(--color-primary), var(--color-accent))',
                    'color' => '#FFFFFF',
                    'hoverBg' => 'linear-gradient(135deg, var(--color-primary-dark), var(--color-accent-dark))',
                    'hoverColor' => '#FFFFFF',
                    'radius' => 'var(--radius-md)',
                    'padding' => '12px 28px',
                    'fontSize' => '15px',
                    'fontWeight' => '600',
                    'shadow' => 'var(--shadow-md)',
                    'border' => 'none',
                    'transition' => 'all 0.3s ease',
                ],
            ],
            [
                'slug' => 'elevated',
                'name' => 'Elevated',
                'is_default' => 1,
                'preset' => [
                    'bg' => '#FFFFFF',
                    'color' => 'var(--color-primary)',
                    'hoverBg' => '#FFFFFF',
                    'hoverColor' => 'var(--color-primary-dark)',
                    'radius' => 'var(--radius-lg)',
                    'padding' => '14px 28px',
                    'fontSize' => '15px',
                    'fontWeight' => '600',
                    'shadow' => 'var(--shadow-lg)',
                    'border' => 'none',
                    'transition' => 'all 0.2s ease',
                ],
            ],
            [
                'slug' => 'minimal',
                'name' => 'Minimal Link',
                'is_default' => 1,
                'preset' => [
                    'bg' => 'transparent',
                    'color' => 'var(--color-primary)',
                    'hoverBg' => 'transparent',
                    'hoverColor' => 'var(--color-primary-dark)',
                    'radius' => '0',
                    'padding' => '4px 0',
                    'fontSize' => '15px',
                    'fontWeight' => '600',
                    'shadow' => 'none',
                    'border' => 'none',
                    'borderBottom' => '2px solid var(--color-primary)',
                    'transition' => 'all 0.2s ease',
                ],
            ],
        ];
    }
}
