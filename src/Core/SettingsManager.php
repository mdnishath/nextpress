<?php

declare(strict_types=1);

namespace NextPressBuilder\Core;

/**
 * Manages plugin-wide settings via wp_options.
 *
 * All settings are stored with the 'npb_' prefix.
 * Supports typed getters and grouped settings.
 */
class SettingsManager
{
    private const PREFIX = 'npb_';

    /** @var array<string, mixed> In-memory cache of loaded settings. */
    private array $cache = [];

    /**
     * Get a setting value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $prefixedKey = self::PREFIX . $key;

        if ( array_key_exists( $prefixedKey, $this->cache ) ) {
            return $this->cache[ $prefixedKey ];
        }

        $value = get_option( $prefixedKey, $default );
        $this->cache[ $prefixedKey ] = $value;

        return $value;
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, mixed $value): bool
    {
        $prefixedKey = self::PREFIX . $key;
        $this->cache[ $prefixedKey ] = $value;

        return update_option( $prefixedKey, $value, false );
    }

    /**
     * Delete a setting.
     */
    public function delete(string $key): bool
    {
        $prefixedKey = self::PREFIX . $key;
        unset( $this->cache[ $prefixedKey ] );

        return delete_option( $prefixedKey );
    }

    /**
     * Check if a setting exists.
     */
    public function has(string $key): bool
    {
        $prefixedKey = self::PREFIX . $key;

        return get_option( $prefixedKey, '___NPB_NOT_SET___' ) !== '___NPB_NOT_SET___';
    }

    /**
     * Get all settings in a group.
     * Group settings are stored as a single serialized array.
     *
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        $value = $this->get( "group_{$group}", [] );

        return is_array( $value ) ? $value : [];
    }

    /**
     * Set a value within a group.
     */
    public function setInGroup(string $group, string $key, mixed $value): bool
    {
        $groupData = $this->group( $group );
        $groupData[ $key ] = $value;

        return $this->set( "group_{$group}", $groupData );
    }

    /**
     * Get a typed string setting.
     */
    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get( $key, $default );

        return is_string( $value ) ? $value : $default;
    }

    /**
     * Get a typed integer setting.
     */
    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get( $key, $default );

        return is_numeric( $value ) ? (int) $value : $default;
    }

    /**
     * Get a typed boolean setting.
     */
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get( $key, $default );

        return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
    }

    /**
     * Get a typed array setting.
     *
     * @return array<mixed>
     */
    public function getArray(string $key, array $default = []): array
    {
        $value = $this->get( $key, $default );

        return is_array( $value ) ? $value : $default;
    }

    /**
     * Get all settings with the npb_ prefix (for export/debug).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                self::PREFIX . '%'
            )
        );

        $settings = [];
        foreach ( $results as $row ) {
            $key = str_replace( self::PREFIX, '', $row->option_name );
            $settings[ $key ] = maybe_unserialize( $row->option_value );
        }

        return $settings;
    }

    /**
     * Clear the in-memory cache.
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Set default settings if they don't exist.
     *
     * @param array<string, mixed> $defaults
     */
    public function setDefaults(array $defaults): void
    {
        foreach ( $defaults as $key => $value ) {
            if ( ! $this->has( $key ) ) {
                $this->set( $key, $value );
            }
        }
    }
}
