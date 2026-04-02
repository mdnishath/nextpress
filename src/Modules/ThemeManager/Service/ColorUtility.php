<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\ThemeManager\Service;

/**
 * Color manipulation utilities.
 *
 * Handles hex-to-HSL conversion, darken/lighten, WCAG contrast checking,
 * and auto shade generation for the theme system.
 */
class ColorUtility
{
    /**
     * Darken a hex color by a percentage.
     */
    public function darken(string $hex, int $percent): string
    {
        [$h, $s, $l] = $this->hexToHsl($hex);
        $l = max(0, $l - ($percent / 100));
        return $this->hslToHex($h, $s, $l);
    }

    /**
     * Lighten a hex color by a percentage.
     */
    public function lighten(string $hex, int $percent): string
    {
        [$h, $s, $l] = $this->hexToHsl($hex);
        $l = min(1, $l + ($percent / 100));
        return $this->hslToHex($h, $s, $l);
    }

    /**
     * Calculate WCAG 2.1 contrast ratio between two colors.
     * Returns a value between 1 and 21.
     */
    public function getContrastRatio(string $fg, string $bg): float
    {
        $l1 = $this->getRelativeLuminance($fg);
        $l2 = $this->getRelativeLuminance($bg);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Check if two colors meet WCAG AA contrast (4.5:1 for normal text).
     */
    public function meetsAA(string $fg, string $bg): bool
    {
        return $this->getContrastRatio($fg, $bg) >= 4.5;
    }

    /**
     * Check if two colors meet WCAG AAA contrast (7:1 for normal text).
     */
    public function meetsAAA(string $fg, string $bg): bool
    {
        return $this->getContrastRatio($fg, $bg) >= 7.0;
    }

    /**
     * Convert hex to HSL. Returns [h, s, l] where h is 0-360, s and l are 0-1.
     *
     * @return array{0: float, 1: float, 2: float}
     */
    public function hexToHsl(string $hex): array
    {
        [$r, $g, $b] = $this->hexToRgb($hex);

        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            return [0.0, 0.0, $l];
        }

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        $h = match (true) {
            $max === $r => (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6,
            $max === $g => (($b - $r) / $d + 2) / 6,
            default     => (($r - $g) / $d + 4) / 6,
        };

        return [$h * 360, $s, $l];
    }

    /**
     * Convert HSL to hex.
     *
     * @param float $h Hue 0-360
     * @param float $s Saturation 0-1
     * @param float $l Lightness 0-1
     */
    public function hslToHex(float $h, float $s, float $l): string
    {
        $h = fmod($h, 360);
        if ($h < 0) $h += 360;

        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        [$r, $g, $b] = match (true) {
            $h < 60  => [$c, $x, 0.0],
            $h < 120 => [$x, $c, 0.0],
            $h < 180 => [0.0, $c, $x],
            $h < 240 => [0.0, $x, $c],
            $h < 300 => [$x, 0.0, $c],
            default  => [$c, 0.0, $x],
        };

        return sprintf(
            '#%02X%02X%02X',
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255)
        );
    }

    /**
     * Convert hex to RGB array.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    public function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Auto-generate dark and light shades from a base color.
     *
     * @return array{dark: string, light: string, 50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string}
     */
    public function autoGenerateShades(string $baseColor): array
    {
        return [
            'dark'  => $this->darken($baseColor, 15),
            'light' => $this->lighten($baseColor, 20),
            '50'    => $this->lighten($baseColor, 45),
            '100'   => $this->lighten($baseColor, 40),
            '200'   => $this->lighten($baseColor, 30),
            '300'   => $this->lighten($baseColor, 20),
            '400'   => $this->lighten($baseColor, 10),
            '500'   => $baseColor,
            '600'   => $this->darken($baseColor, 10),
            '700'   => $this->darken($baseColor, 20),
            '800'   => $this->darken($baseColor, 30),
            '900'   => $this->darken($baseColor, 40),
        ];
    }

    /**
     * Get relative luminance of a color (for WCAG contrast calculation).
     */
    private function getRelativeLuminance(string $hex): float
    {
        [$r, $g, $b] = $this->hexToRgb($hex);

        $r = $this->linearize($r / 255);
        $g = $this->linearize($g / 255);
        $b = $this->linearize($b / 255);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Linearize a sRGB value for luminance calculation.
     */
    private function linearize(float $value): float
    {
        return $value <= 0.03928
            ? $value / 12.92
            : pow(($value + 0.055) / 1.055, 2.4);
    }
}
