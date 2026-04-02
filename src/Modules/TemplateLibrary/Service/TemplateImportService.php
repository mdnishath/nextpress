<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\TemplateLibrary\Service;

use NextPressBuilder\Core\Repository\FormRepository;
use NextPressBuilder\Core\Repository\NavigationRepository;
use NextPressBuilder\Core\Repository\PageRepository;
use NextPressBuilder\Core\Repository\SectionRepository;
use NextPressBuilder\Core\Repository\ThemeRepository;
use NextPressBuilder\Core\SettingsManager;

/**
 * Imports a complete website from template data.
 *
 * One-click import creates: theme, pages, sections, navigation, forms.
 * Replaces template variables: {business_name}, {phone}, {email}, {city}, etc.
 */
class TemplateImportService
{
    public function __construct(
        private readonly PageRepository $pageRepo,
        private readonly SectionRepository $sectionRepo,
        private readonly ThemeRepository $themeRepo,
        private readonly NavigationRepository $navRepo,
        private readonly FormRepository $formRepo,
        private readonly SettingsManager $settings,
    ) {}

    /**
     * Import a full template.
     *
     * @param array<string, mixed> $templateData  The template's 'data' field.
     * @param array<string, string> $vars         User-provided variables.
     * @return array{pages: int, sections: int, forms: int}
     */
    public function import(array $templateData, array $vars = []): array
    {
        $counts = ['pages' => 0, 'sections' => 0, 'forms' => 0];

        // 1. Update business settings.
        $this->applyBusinessSettings($vars);

        // 2. Import theme.
        if (!empty($templateData['theme'])) {
            $this->importTheme($templateData['theme'], $vars);
        }

        // 3. Import navigation.
        if (!empty($templateData['navigation'])) {
            foreach ($templateData['navigation'] as $menu) {
                $menu = $this->replaceVars($menu, $vars);
                $menu['items'] = $this->replaceVarsRecursive($menu['items'] ?? [], $vars);

                // Delete existing menu with same slug.
                $existing = $this->navRepo->findBySlug($menu['slug'] ?? '');
                if ($existing) {
                    $this->navRepo->delete((int) $existing->id);
                }

                $this->navRepo->create($menu);
            }
        }

        // 4. Import forms.
        $formMap = [];
        if (!empty($templateData['forms'])) {
            foreach ($templateData['forms'] as $form) {
                $form = $this->replaceVars($form, $vars);

                $existing = $this->formRepo->findBySlug($form['slug'] ?? '');
                if ($existing) {
                    $this->formRepo->delete((int) $existing->id);
                }

                $id = $this->formRepo->create($form);
                $formMap[$form['slug']] = $id;
                $counts['forms']++;
            }
        }

        // 5. Import pages with sections.
        if (!empty($templateData['pages'])) {
            foreach ($templateData['pages'] as $pageData) {
                $sections = $pageData['sections'] ?? [];
                unset($pageData['sections']);

                $pageData = $this->replaceVars($pageData, $vars);

                // Delete existing page with same slug.
                $existing = $this->pageRepo->findBySlug($pageData['slug'] ?? '');
                if ($existing) {
                    $this->sectionRepo->deleteByPage((int) $existing->id);
                    $this->pageRepo->delete((int) $existing->id);
                }

                $pageData['status'] = 'published';
                $pageId = $this->pageRepo->create($pageData);
                $counts['pages']++;

                // Import sections.
                foreach ($sections as $sortOrder => $sectionData) {
                    $sectionData = $this->replaceVars($sectionData, $vars);
                    $sectionData['page_id'] = $pageId;
                    $sectionData['sort_order'] = $sortOrder;
                    $sectionData['enabled'] = 1;

                    $this->sectionRepo->create($sectionData);
                    $counts['sections']++;
                }
            }
        }

        return $counts;
    }

    /**
     * Apply business settings from import variables.
     *
     * @param array<string, string> $vars
     */
    private function applyBusinessSettings(array $vars): void
    {
        $map = [
            'business_name'  => 'business_name',
            'phone'          => 'business_phone',
            'email'          => 'business_email',
            'address'        => 'business_address',
            'city'           => 'business_city',
            'state'          => 'business_state',
            'zip'            => 'business_zip',
        ];

        foreach ($map as $varKey => $settingsKey) {
            if (!empty($vars[$varKey])) {
                $this->settings->set($settingsKey, $vars[$varKey]);
            }
        }
    }

    /**
     * Import and activate a theme from template data.
     *
     * @param array<string, mixed> $themeData
     * @param array<string, string> $vars
     */
    private function importTheme(array $themeData, array $vars): void
    {
        $slug = $themeData['slug'] ?? 'imported-' . time();
        $existing = $this->themeRepo->findBySlug($slug);
        if ($existing) {
            $this->themeRepo->delete((int) $existing->id);
        }

        $themeData['is_active'] = 0;
        $id = $this->themeRepo->create($themeData);
        $this->themeRepo->activate($id);
    }

    /**
     * Replace {variable} placeholders in data.
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $vars
     * @return array<string, mixed>
     */
    private function replaceVars(array $data, array $vars): array
    {
        $search = [];
        $replace = [];
        foreach ($vars as $key => $value) {
            $search[] = '{' . $key . '}';
            $replace[] = $value;
        }

        array_walk_recursive($data, function (&$item) use ($search, $replace) {
            if (is_string($item)) {
                $item = str_replace($search, $replace, $item);
            }
        });

        return $data;
    }

    /**
     * Replace vars recursively in nested arrays (menus, etc.).
     *
     * @param array<int, mixed> $items
     * @param array<string, string> $vars
     * @return array<int, mixed>
     */
    private function replaceVarsRecursive(array $items, array $vars): array
    {
        foreach ($items as &$item) {
            if (is_array($item)) {
                $item = $this->replaceVars($item, $vars);
                if (isset($item['children']) && is_array($item['children'])) {
                    $item['children'] = $this->replaceVarsRecursive($item['children'], $vars);
                }
            }
        }
        return $items;
    }
}
