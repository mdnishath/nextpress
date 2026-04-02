<?php

declare(strict_types=1);

namespace NextPressBuilder\Core;

/**
 * Centralized input sanitization.
 *
 * Type-aware sanitization for all input types used across the plugin.
 * Every user input must pass through this class before storage.
 */
class Sanitizer
{
    /**
     * Sanitize plain text (strips tags, removes extra whitespace).
     */
    public function text(string $input): string
    {
        return sanitize_text_field( $input );
    }

    /**
     * Sanitize HTML content (allows safe tags).
     */
    public function html(string $input): string
    {
        return wp_kses_post( $input );
    }

    /**
     * Sanitize a textarea (preserves newlines).
     */
    public function textarea(string $input): string
    {
        return sanitize_textarea_field( $input );
    }

    /**
     * Sanitize a URL.
     */
    public function url(string $input): string
    {
        return esc_url_raw( $input );
    }

    /**
     * Sanitize an email address.
     */
    public function email(string $input): string
    {
        return sanitize_email( $input );
    }

    /**
     * Sanitize a hex color value.
     * Returns empty string if invalid.
     */
    public function color(string $input): string
    {
        // Support 3, 4, 6, or 8 character hex colors.
        if ( preg_match( '/^#([A-Fa-f0-9]{3,4}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/', $input ) ) {
            return $input;
        }

        // Support CSS color functions: rgb(), rgba(), hsl(), hsla().
        if ( preg_match( '/^(rgb|rgba|hsl|hsla)\([\d\s,%.]+\)$/', $input ) ) {
            return $input;
        }

        // Support CSS variables.
        if ( preg_match( '/^var\(--[\w-]+\)$/', $input ) ) {
            return $input;
        }

        return '';
    }

    /**
     * Sanitize a slug (lowercase alphanumeric + hyphens).
     */
    public function slug(string $input): string
    {
        return sanitize_title( $input );
    }

    /**
     * Sanitize an integer.
     */
    public function int(mixed $input): int
    {
        return (int) $input;
    }

    /**
     * Sanitize a positive integer (absint).
     */
    public function absint(mixed $input): int
    {
        return absint( $input );
    }

    /**
     * Sanitize a float.
     */
    public function float(mixed $input): float
    {
        return (float) filter_var( $input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
    }

    /**
     * Sanitize a boolean.
     */
    public function bool(mixed $input): bool
    {
        return filter_var( $input, FILTER_VALIDATE_BOOLEAN );
    }

    /**
     * Sanitize and decode a JSON string.
     * Returns null if invalid JSON.
     *
     * @return array<mixed>|null
     */
    public function json(string $input): ?array
    {
        $decoded = json_decode( $input, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return null;
        }

        return is_array( $decoded ) ? $decoded : null;
    }

    /**
     * Sanitize a file name.
     */
    public function fileName(string $input): string
    {
        return sanitize_file_name( $input );
    }

    /**
     * Sanitize a CSS class name.
     */
    public function cssClass(string $input): string
    {
        return sanitize_html_class( $input );
    }

    /**
     * Sanitize a CSS value (basic protection against injection).
     */
    public function cssValue(string $input): string
    {
        // Remove potentially dangerous characters.
        $clean = preg_replace( '/[<>"\'()]/', '', $input );
        // Remove expression(), url() and similar.
        $clean = preg_replace( '/\b(expression|javascript|import)\s*\(/i', '', $clean ?? '' );

        return $clean ?? '';
    }

    /**
     * Sanitize an array of values using a specified sanitizer.
     *
     * @param array<mixed> $input
     * @return array<mixed>
     */
    public function array(array $input, string $method = 'text'): array
    {
        return array_map(
            fn( mixed $value ) => is_string( $value ) ? $this->{$method}( $value ) : $value,
            $input
        );
    }

    /**
     * Sanitize data against a schema.
     * Schema: ['field_name' => 'sanitizer_method', ...]
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $schema
     * @return array<string, mixed>
     */
    public function withSchema(array $data, array $schema): array
    {
        $sanitized = [];

        foreach ( $schema as $key => $method ) {
            if ( ! array_key_exists( $key, $data ) ) {
                continue;
            }

            $value = $data[ $key ];

            if ( method_exists( $this, $method ) ) {
                $sanitized[ $key ] = is_string( $value ) || is_numeric( $value )
                    ? $this->{$method}( $value )
                    : $value;
            } else {
                $sanitized[ $key ] = $value;
            }
        }

        return $sanitized;
    }
}
