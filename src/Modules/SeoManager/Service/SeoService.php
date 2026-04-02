<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\SeoManager\Service;

use NextPressBuilder\Core\SettingsManager;

/**
 * SEO data management: global defaults, per-page SEO, sitemap, redirects.
 * Replaces Yoast/RankMath for headless WordPress.
 */
class SeoService
{
    public function __construct(
        private readonly SettingsManager $settings,
    ) {}

    /**
     * Get global SEO settings.
     *
     * @return array<string, mixed>
     */
    public function getGlobalSeo(): array
    {
        return [
            'title_template'      => $this->settings->getString('seo_title_template', '{page_title} | {site_name}'),
            'default_description' => $this->settings->getString('seo_default_description', ''),
            'default_og_image'    => $this->settings->getString('seo_default_og_image', ''),
            'robots'              => [
                'index'  => $this->settings->getBool('seo_robots_index', true),
                'follow' => $this->settings->getBool('seo_robots_follow', true),
            ],
            'site_name'           => get_bloginfo('name'),
            'site_url'            => home_url(),
            'separator'           => $this->settings->getString('seo_separator', '|'),
            'social'              => [
                'facebook_url' => $this->settings->getString('seo_facebook_url', ''),
                'twitter_handle' => $this->settings->getString('seo_twitter_handle', ''),
                'instagram_url' => $this->settings->getString('seo_instagram_url', ''),
                'linkedin_url' => $this->settings->getString('seo_linkedin_url', ''),
                'youtube_url' => $this->settings->getString('seo_youtube_url', ''),
            ],
        ];
    }

    /**
     * Update global SEO settings.
     *
     * @param array<string, mixed> $data
     */
    public function updateGlobalSeo(array $data): void
    {
        $fields = [
            'seo_title_template', 'seo_default_description', 'seo_default_og_image',
            'seo_robots_index', 'seo_robots_follow', 'seo_separator',
            'seo_facebook_url', 'seo_twitter_handle', 'seo_instagram_url',
            'seo_linkedin_url', 'seo_youtube_url',
        ];

        foreach ($fields as $key) {
            $shortKey = str_replace('seo_', '', $key);
            if (array_key_exists($shortKey, $data)) {
                $this->settings->set($key, $data[$shortKey]);
            }
        }
    }

    /**
     * Build page SEO from npb_pages record.
     *
     * @return array<string, mixed>
     */
    public function buildPageSeo(object $page): array
    {
        $global = $this->getGlobalSeo();
        $siteName = $global['site_name'];

        $title = $page->seo_title ?? '';
        if (!$title) {
            $template = $global['title_template'];
            $title = str_replace(
                ['{page_title}', '{site_name}'],
                [$page->title ?? '', $siteName],
                $template
            );
        }

        $description = $page->seo_description ?? $global['default_description'];
        $ogImage = $page->og_image ?? $global['default_og_image'];

        return [
            'title'       => $title,
            'description' => $description,
            'keywords'    => $page->seo_keywords ?? '',
            'canonical'   => home_url('/' . ($page->slug ?? '')),
            'robots'      => $global['robots'],
            'og'          => [
                'title'       => $title,
                'description' => $description,
                'image'       => $ogImage,
                'type'        => 'website',
                'site_name'   => $siteName,
                'url'         => home_url('/' . ($page->slug ?? '')),
            ],
            'twitter'     => [
                'card'        => 'summary_large_image',
                'title'       => $title,
                'description' => $description,
                'image'       => $ogImage,
            ],
        ];
    }

    /**
     * Build sitemap data from published pages.
     *
     * @param object[] $pages Published npb_pages records.
     * @return array<int, array<string, mixed>>
     */
    public function buildSitemap(array $pages): array
    {
        $urls = [];

        foreach ($pages as $page) {
            if (($page->page_type ?? '') !== 'page') continue;

            $slug = $page->slug ?? '';
            $loc = $slug === 'home' ? '/' : '/' . $slug;

            $urls[] = [
                'loc'        => $loc,
                'lastmod'    => substr($page->updated_at ?? gmdate('Y-m-d'), 0, 10),
                'changefreq' => $slug === 'home' ? 'weekly' : 'monthly',
                'priority'   => $slug === 'home' ? 1.0 : 0.8,
            ];
        }

        return $urls;
    }

    /**
     * Get all redirects.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRedirects(): array
    {
        return $this->settings->getArray('seo_redirects', []);
    }

    /**
     * Add a redirect.
     */
    public function addRedirect(string $from, string $to, int $type = 301): void
    {
        $redirects = $this->getRedirects();
        $redirects[] = ['from' => $from, 'to' => $to, 'type' => $type];
        $this->settings->set('seo_redirects', $redirects);
    }

    /**
     * Remove a redirect by index.
     */
    public function removeRedirect(int $index): void
    {
        $redirects = $this->getRedirects();
        unset($redirects[$index]);
        $this->settings->set('seo_redirects', array_values($redirects));
    }
}
