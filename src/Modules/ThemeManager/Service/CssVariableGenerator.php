<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\ThemeManager\Service;

/**
 * Converts theme token data into CSS custom properties.
 *
 * Generates the :root CSS that the Next.js frontend injects into <html>.
 * Includes dark mode overrides via @media (prefers-color-scheme: dark).
 */
class CssVariableGenerator
{
    /**
     * Generate the complete CSS custom properties string from theme data.
     */
    public function generate(object $theme): string
    {
        $css = ":root {\n";
        $css .= $this->generateColors($theme->colors ?? new \stdClass());
        $css .= $this->generateTypography($theme->typography ?? new \stdClass());
        $css .= $this->generateSpacing($theme->spacing ?? new \stdClass());
        $css .= $this->generateBorders($theme->borders ?? new \stdClass());
        $css .= $this->generateShadows($theme->shadows ?? new \stdClass());
        $css .= "}\n";

        // Dark mode overrides.
        $darkMode = $theme->dark_mode ?? null;
        if ($darkMode && !empty($darkMode->enabled) && !empty($darkMode->colors)) {
            $css .= $this->generateDarkMode($darkMode);
        }

        return $css;
    }

    /**
     * Generate color CSS variables.
     */
    private function generateColors(object $colors): string
    {
        $css = "  /* Colors */\n";

        foreach ((array) $colors as $name => $value) {
            if (is_object($value)) {
                $hex = $value->value ?? '';
            } elseif (is_string($value)) {
                $hex = $value;
            } else {
                continue;
            }

            if ($hex) {
                $css .= "  --color-{$name}: {$hex};\n";
            }
        }

        return $css . "\n";
    }

    /**
     * Generate typography CSS variables.
     */
    private function generateTypography(object $typography): string
    {
        $css = "  /* Typography */\n";

        // Font families.
        $families = ['heading', 'body', 'mono'];
        foreach ($families as $key) {
            $font = $typography->{$key} ?? null;
            if ($font && !empty($font->family)) {
                $fallback = $font->fallback ?? ($key === 'mono' ? 'monospace' : 'system-ui, sans-serif');
                $css .= "  --font-{$key}: '{$font->family}', {$fallback};\n";
            }
        }

        $css .= "\n";

        // Type scale.
        $scale = $typography->scale ?? new \stdClass();
        foreach ((array) $scale as $name => $size) {
            if (is_object($size)) {
                $css .= "  --text-{$name}: {$size->size};\n";
                if (!empty($size->lineHeight)) {
                    $css .= "  --leading-{$name}: {$size->lineHeight};\n";
                }
            } elseif (is_string($size)) {
                $css .= "  --text-{$name}: {$size};\n";
            }
        }

        return $css . "\n";
    }

    /**
     * Generate spacing CSS variables.
     */
    private function generateSpacing(object $spacing): string
    {
        $css = "  /* Spacing */\n";

        $base = $spacing->base ?? 4;
        $css .= "  --spacing-base: {$base}px;\n";

        // Spacing scale.
        $scale = $spacing->scale ?? new \stdClass();
        foreach ((array) $scale as $name => $value) {
            $css .= "  --spacing-{$name}: {$value};\n";
        }

        // Container.
        $maxWidth = $spacing->containerMaxWidth ?? '1280px';
        $css .= "  --container-max-width: {$maxWidth};\n";

        // Container padding (responsive — use mobile as default, override in media queries).
        $containerPad = $spacing->containerPadding ?? null;
        if ($containerPad) {
            $mobilePad = $containerPad->mobile ?? '16px';
            $css .= "  --container-padding: {$mobilePad};\n";
        }

        return $css . "\n";
    }

    /**
     * Generate border radius CSS variables.
     */
    private function generateBorders(object $borders): string
    {
        $css = "  /* Borders */\n";

        $defaults = ['sm' => '4px', 'md' => '8px', 'lg' => '12px', 'xl' => '16px', 'full' => '9999px'];
        $values = array_merge($defaults, (array) $borders);

        foreach ($values as $name => $value) {
            $css .= "  --radius-{$name}: {$value};\n";
        }

        return $css . "\n";
    }

    /**
     * Generate shadow CSS variables.
     */
    private function generateShadows(object $shadows): string
    {
        $css = "  /* Shadows */\n";

        $defaults = [
            'sm'  => '0 1px 2px rgba(0,0,0,0.05)',
            'md'  => '0 4px 6px rgba(0,0,0,0.1)',
            'lg'  => '0 10px 15px rgba(0,0,0,0.1)',
            'xl'  => '0 20px 25px rgba(0,0,0,0.1)',
            '2xl' => '0 25px 50px rgba(0,0,0,0.15)',
        ];
        $values = array_merge($defaults, (array) $shadows);

        foreach ($values as $name => $value) {
            $css .= "  --shadow-{$name}: {$value};\n";
        }

        return $css . "\n";
    }

    /**
     * Generate dark mode CSS overrides.
     */
    private function generateDarkMode(object $darkMode): string
    {
        $toggle = $darkMode->toggle ?? 'system';
        $colors = (array) ($darkMode->colors ?? []);

        if (empty($colors)) {
            return '';
        }

        $css = "\n/* Dark Mode */\n";

        if ($toggle === 'system') {
            $css .= "@media (prefers-color-scheme: dark) {\n";
            $css .= "  :root[data-theme=\"auto\"], :root[data-theme=\"dark\"] {\n";
        } else {
            $css .= ":root[data-theme=\"dark\"] {\n";
        }

        foreach ($colors as $name => $hex) {
            if (is_string($hex) && $hex) {
                $css .= "    --color-{$name}: {$hex};\n";
            }
        }

        if ($toggle === 'system') {
            $css .= "  }\n}\n";
        } else {
            $css .= "}\n";
        }

        return $css;
    }

    /**
     * Generate font loading config for Next.js.
     *
     * @return array<int, array{family: string, weights: int[], source: string}>
     */
    public function getFontConfig(object $typography): array
    {
        $fonts = [];
        $families = ['heading', 'body', 'mono'];

        foreach ($families as $key) {
            $font = $typography->{$key} ?? null;
            if ($font && !empty($font->family)) {
                $fonts[] = [
                    'family'  => $font->family,
                    'weights' => (array) ($font->weights ?? [400]),
                    'source'  => $font->source ?? 'google',
                    'role'    => $key,
                ];
            }
        }

        return $fonts;
    }
}
