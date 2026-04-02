<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\SchemaManager\Service;

use NextPressBuilder\Core\SettingsManager;

/**
 * Auto-generates JSON-LD structured data for pages.
 * Supports 20 business types + FAQ, Breadcrumb, Service schemas.
 */
class SchemaService
{
    public function __construct(
        private readonly SettingsManager $settings,
    ) {}

    /**
     * Get global Organization schema.
     *
     * @return array<string, mixed>
     */
    public function getGlobalSchema(): array
    {
        $name = $this->settings->getString('business_name', get_bloginfo('name'));

        return [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => $name,
            'url'      => home_url(),
            'email'    => $this->settings->getString('business_email'),
            'telephone'=> $this->settings->getString('business_phone'),
            'address'  => $this->buildAddress(),
            'geo'      => $this->buildGeo(),
        ];
    }

    /**
     * Build page-level schema based on schema_type.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildPageSchema(object $page, array $sections = []): array
    {
        $schemas = [];

        // Business type schema.
        $type = $page->schema_type ?? '';
        if ($type) {
            $schemas[] = $this->buildBusinessSchema($type, $page);
        }

        // WebPage schema.
        $schemas[] = $this->buildWebPageSchema($page);

        // BreadcrumbList.
        $schemas[] = $this->buildBreadcrumb($page);

        // FAQ schema from FAQ sections.
        foreach ($sections as $section) {
            $section = (object) $section;
            if (($section->section_type ?? '') === 'faq') {
                $faqSchema = $this->buildFaqSchema($section);
                if ($faqSchema) $schemas[] = $faqSchema;
            }
        }

        // Merge page-level schema overrides.
        if (!empty($page->schema_data)) {
            $overrides = is_object($page->schema_data) ? (array) $page->schema_data : $page->schema_data;
            if (is_array($overrides) && !empty($overrides)) {
                $schemas[] = array_merge(['@context' => 'https://schema.org'], $overrides);
            }
        }

        return array_filter($schemas);
    }

    /**
     * Build business type schema.
     *
     * @return array<string, mixed>
     */
    private function buildBusinessSchema(string $type, object $page): array
    {
        $name = $this->settings->getString('business_name', get_bloginfo('name'));

        $schema = [
            '@context'  => 'https://schema.org',
            '@type'     => $type,
            'name'      => $name,
            'url'       => home_url(),
            'telephone' => $this->settings->getString('business_phone'),
            'email'     => $this->settings->getString('business_email'),
            'address'   => $this->buildAddress(),
        ];

        $geo = $this->buildGeo();
        if ($geo) $schema['geo'] = $geo;

        // Type-specific additions.
        $schema = match ($type) {
            'Restaurant' => array_merge($schema, [
                'servesCuisine' => '',
                'priceRange'    => '$$',
                'menu'          => home_url('/menu'),
            ]),
            'LegalService' => array_merge($schema, [
                'priceRange' => '$$$',
            ]),
            'MedicalBusiness', 'Dentist' => array_merge($schema, [
                'medicalSpecialty' => '',
            ]),
            default => $schema,
        };

        return $schema;
    }

    /**
     * Build WebPage schema.
     *
     * @return array<string, mixed>
     */
    private function buildWebPageSchema(object $page): array
    {
        return [
            '@context'    => 'https://schema.org',
            '@type'       => 'WebPage',
            'name'        => $page->title ?? '',
            'description' => $page->seo_description ?? '',
            'url'         => home_url('/' . ($page->slug ?? '')),
            'dateModified'=> $page->updated_at ?? '',
        ];
    }

    /**
     * Build BreadcrumbList schema.
     *
     * @return array<string, mixed>
     */
    private function buildBreadcrumb(object $page): array
    {
        $items = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url('/')],
        ];

        $slug = $page->slug ?? '';
        if ($slug && $slug !== 'home') {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => 2,
                'name'     => $page->title ?? $slug,
                'item'     => home_url('/' . $slug),
            ];
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Build FAQPage schema from a FAQ section.
     *
     * @return array<string, mixed>|null
     */
    private function buildFaqSchema(object $section): ?array
    {
        $content = is_object($section->content) ? (array) $section->content : ($section->content ?? []);
        $items = $content['items'] ?? [];

        if (empty($items)) return null;

        $faqEntries = [];
        foreach ($items as $item) {
            $item = (object) $item;
            if (!empty($item->question) && !empty($item->answer)) {
                $faqEntries[] = [
                    '@type'          => 'Question',
                    'name'           => $item->question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => strip_tags($item->answer),
                    ],
                ];
            }
        }

        if (empty($faqEntries)) return null;

        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $faqEntries,
        ];
    }

    /**
     * Build PostalAddress.
     *
     * @return array<string, string>
     */
    private function buildAddress(): array
    {
        return [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $this->settings->getString('business_address'),
            'addressLocality' => $this->settings->getString('business_city'),
            'addressRegion'   => $this->settings->getString('business_state'),
            'postalCode'      => $this->settings->getString('business_zip'),
            'addressCountry'  => $this->settings->getString('business_country'),
        ];
    }

    /**
     * Build GeoCoordinates.
     *
     * @return array<string, mixed>|null
     */
    private function buildGeo(): ?array
    {
        $lat = $this->settings->getString('business_latitude');
        $lng = $this->settings->getString('business_longitude');

        if (!$lat || !$lng) return null;

        return [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float) $lat,
            'longitude' => (float) $lng,
        ];
    }

    /**
     * Get the list of supported business schema types.
     *
     * @return string[]
     */
    public static function supportedTypes(): array
    {
        return [
            'LocalBusiness', 'Restaurant', 'LegalService', 'Dentist', 'Plumber',
            'Electrician', 'HomeRepair', 'MedicalBusiness', 'FinancialService',
            'RealEstateAgent', 'AutomotiveBusiness', 'BeautySalon', 'FitnessCenter',
            'EducationalOrganization', 'GeneralContractor', 'VeterinaryCare',
            'MovingCompany', 'RoofingContractor', 'LandscapingBusiness',
        ];
    }
}
