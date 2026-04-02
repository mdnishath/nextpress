<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\ComponentLibrary\Service;

use NextPressBuilder\Core\Repository\VariantRepository;

/**
 * Manages style variants for each component.
 *
 * Each component has 20 structural variants defining layout, not just colors.
 * Variants determine how content is arranged (split, centered, grid, etc.).
 */
class VariantService
{
    public function __construct(
        private readonly VariantRepository $repo,
    ) {}

    /**
     * Seed default variants for all components if none exist.
     */
    public function seedVariants(): void
    {
        if ($this->repo->count() > 0) {
            return;
        }

        foreach ($this->getAllVariants() as $variant) {
            $this->repo->create($variant);
        }
    }

    /**
     * Get variant definitions for all 20 components.
     * Starting with 5 variants per component (100 total) for MVP.
     * Will expand to 20 each (400 total) in Phase 3.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAllVariants(): array
    {
        $variants = [];

        // Hero variants (5 of 20)
        $heroVariants = [
            ['variant-01','Fullscreen Centered','layout'=>'fullscreen-center','textAlign'=>'center','contentWidth'=>'100%','showImage'=>true,'imageStyle'=>'background'],
            ['variant-02','Fullscreen + Form','layout'=>'fullscreen-form','textAlign'=>'center','showForm'=>true,'imageStyle'=>'background'],
            ['variant-03','Split: Text Left, Image Right','layout'=>'split-right','textAlign'=>'left','contentWidth'=>'50%','imagePosition'=>'right'],
            ['variant-04','Split: Image Left, Text Right','layout'=>'split-left','textAlign'=>'left','contentWidth'=>'50%','imagePosition'=>'left'],
            ['variant-05','Split: Text Left, Form Right','layout'=>'split-form','textAlign'=>'left','contentWidth'=>'50%','showForm'=>true],
        ];
        foreach ($heroVariants as $i => $v) {
            $variants[] = $this->makeVariant('hero', $v[0], $v[1], array_slice($v, 2), $i);
        }

        // Services Grid variants
        $servicesVariants = [
            ['variant-01','3-Column Card Grid','layout'=>'card-grid','columns'=>3,'cardStyle'=>'elevated','showIcon'=>true],
            ['variant-02','2-Column with Images','layout'=>'card-grid','columns'=>2,'cardStyle'=>'flat','showImage'=>true],
            ['variant-03','Icon List (No Cards)','layout'=>'icon-list','columns'=>1,'cardStyle'=>'none','showIcon'=>true],
            ['variant-04','Alternating Image + Text','layout'=>'alternating','columns'=>1,'showImage'=>true],
            ['variant-05','Cards with Hover Effect','layout'=>'card-grid','columns'=>3,'cardStyle'=>'hover-overlay','showImage'=>true],
        ];
        foreach ($servicesVariants as $i => $v) {
            $variants[] = $this->makeVariant('services_grid', $v[0], $v[1], array_slice($v, 2), $i);
        }

        // Testimonials variants
        $testimonialVariants = [
            ['variant-01','Horizontal Slider','layout'=>'slider','showRating'=>true,'showAvatar'=>true],
            ['variant-02','3-Column Grid','layout'=>'grid','columns'=>3,'showRating'=>true,'showAvatar'=>true],
            ['variant-03','Single Large Quote','layout'=>'single-large','showRating'=>true,'showAvatar'=>true],
            ['variant-04','Cards with Star Ratings','layout'=>'card-grid','columns'=>3,'cardStyle'=>'elevated','showRating'=>true],
            ['variant-05','Minimal Text Quotes','layout'=>'minimal','showRating'=>false,'showAvatar'=>false],
        ];
        foreach ($testimonialVariants as $i => $v) {
            $variants[] = $this->makeVariant('testimonials', $v[0], $v[1], array_slice($v, 2), $i);
        }

        // CTA Banner variants
        $ctaVariants = [
            ['variant-01','Full-Width Gradient','layout'=>'fullwidth','bgType'=>'gradient','textAlign'=>'center'],
            ['variant-02','Background Image + Overlay','layout'=>'fullwidth','bgType'=>'image','textAlign'=>'center'],
            ['variant-03','Split: Text Left, Image Right','layout'=>'split','textAlign'=>'left'],
            ['variant-04','Compact Inline','layout'=>'inline','textAlign'=>'left'],
            ['variant-05','With Form Embed','layout'=>'with-form','textAlign'=>'left'],
        ];
        foreach ($ctaVariants as $i => $v) {
            $variants[] = $this->makeVariant('cta_banner', $v[0], $v[1], array_slice($v, 2), $i);
        }

        // Pricing variants
        $pricingVariants = [
            ['variant-01','3-Column Cards','layout'=>'card-grid','columns'=>3,'highlightStyle'=>'border'],
            ['variant-02','2-Column Comparison','layout'=>'comparison','columns'=>2],
            ['variant-03','Horizontal Table','layout'=>'table','columns'=>4],
            ['variant-04','Cards with Toggle','layout'=>'card-grid','columns'=>3,'showToggle'=>true],
            ['variant-05','Single Featured Plan','layout'=>'single-featured','columns'=>3],
        ];
        foreach ($pricingVariants as $i => $v) {
            $variants[] = $this->makeVariant('pricing', $v[0], $v[1], array_slice($v, 2), $i);
        }

        // Generate simple 5 variants for remaining components
        $simpleComponents = [
            'team' => ['Card Grid','Circular Photos','List with Bio','Carousel','Compact Grid'],
            'faq' => ['Classic Accordion','Side-by-Side','Searchable','Categorized','Minimal'],
            'gallery' => ['Grid','Masonry','Slider','Lightbox Grid','Filtered Grid'],
            'stats_counter' => ['Horizontal Row','Card Grid','Large Numbers','With Icons','Compact'],
            'contact_form' => ['Form + Map Side by Side','Form Centered','Form + Info Cards','Split Layout','Full Width Form'],
            'timeline' => ['Vertical Alternating','Vertical Left','Horizontal','Compact','With Images'],
            'portfolio' => ['Grid with Filters','Masonry','Slider','Card Grid','Full Width'],
            'blog_news' => ['Card Grid','List','Featured + Grid','Magazine Layout','Minimal'],
            'newsletter' => ['Centered','Split with Image','Inline','Full Width Banner','Compact'],
            'about' => ['Split Image + Text','Full Width','Values Grid','Timeline Story','Team Focused'],
            'zone_intervention' => ['Map + List','List Only','Map Full Width','Cards Grid','Compact'],
            'before_after' => ['Slider','Side by Side','Stacked','Carousel','Grid'],
            'rich_text' => ['Centered','Left Aligned','Two Column','With Sidebar','Full Width'],
            'divider' => ['Simple Line','Wave','Angle','Dots','Gradient'],
            'embed' => ['Standard','Full Width','With Caption','Side by Side','Compact'],
        ];

        foreach ($simpleComponents as $slug => $names) {
            foreach ($names as $i => $name) {
                $variantSlug = 'variant-' . str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT);
                $variants[] = [
                    'component_slug' => $slug,
                    'variant_slug'   => $variantSlug,
                    'name'           => $name,
                    'style'          => ['layout' => strtolower(str_replace([' ', '+', '&'], ['-', '', ''], $name))],
                    'sort_order'     => $i,
                ];
            }
        }

        return $variants;
    }

    /**
     * Helper to build a variant array.
     *
     * @param array<string, mixed> $style
     */
    private function makeVariant(string $component, string $variantSlug, string $name, array $style, int $order): array
    {
        return [
            'component_slug' => $component,
            'variant_slug'   => $variantSlug,
            'name'           => $name,
            'style'          => $style,
            'sort_order'     => $order,
        ];
    }
}
