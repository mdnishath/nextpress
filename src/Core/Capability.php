<?php

declare(strict_types=1);

namespace NextPressBuilder\Core;

/**
 * Manages custom capabilities for NextPress Builder.
 *
 * Defines granular permissions and maps them to WordPress roles.
 */
class Capability
{
    // Page management.
    public const EDIT_PAGES = 'npb_edit_pages';

    // Theme management.
    public const MANAGE_THEMES = 'npb_manage_themes';

    // Form management.
    public const MANAGE_FORMS = 'npb_manage_forms';

    // Plugin settings.
    public const MANAGE_SETTINGS = 'npb_manage_settings';

    // Template import/export.
    public const MANAGE_TEMPLATES = 'npb_manage_templates';

    // Component library.
    public const MANAGE_COMPONENTS = 'npb_manage_components';

    // Navigation menus.
    public const MANAGE_NAVIGATION = 'npb_manage_navigation';

    // SEO settings.
    public const MANAGE_SEO = 'npb_manage_seo';

    /**
     * All custom capabilities.
     *
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::EDIT_PAGES,
            self::MANAGE_THEMES,
            self::MANAGE_FORMS,
            self::MANAGE_SETTINGS,
            self::MANAGE_TEMPLATES,
            self::MANAGE_COMPONENTS,
            self::MANAGE_NAVIGATION,
            self::MANAGE_SEO,
        ];
    }

    /**
     * Register all custom capabilities and assign to roles.
     * Called on plugin activation.
     */
    public function register(): void
    {
        $administrator = get_role( 'administrator' );
        $editor = get_role( 'editor' );

        if ( $administrator ) {
            foreach ( self::all() as $cap ) {
                $administrator->add_cap( $cap, true );
            }
        }

        // Editors get page editing, form management, navigation, and SEO.
        if ( $editor ) {
            $editorCaps = [
                self::EDIT_PAGES,
                self::MANAGE_FORMS,
                self::MANAGE_NAVIGATION,
                self::MANAGE_SEO,
            ];

            foreach ( $editorCaps as $cap ) {
                $editor->add_cap( $cap, true );
            }
        }
    }

    /**
     * Remove all custom capabilities from all roles.
     * Called on plugin deactivation or uninstall.
     */
    public function remove(): void
    {
        $roles = [ 'administrator', 'editor', 'author', 'contributor', 'subscriber' ];

        foreach ( $roles as $roleName ) {
            $role = get_role( $roleName );
            if ( $role ) {
                foreach ( self::all() as $cap ) {
                    $role->remove_cap( $cap );
                }
            }
        }
    }

    /**
     * Check if the current user has a specific capability.
     */
    public static function currentUserCan(string $capability): bool
    {
        return current_user_can( $capability );
    }

    /**
     * Check if the current user can edit pages.
     */
    public static function canEditPages(): bool
    {
        return self::currentUserCan( self::EDIT_PAGES );
    }

    /**
     * Check if the current user can manage themes.
     */
    public static function canManageThemes(): bool
    {
        return self::currentUserCan( self::MANAGE_THEMES );
    }

    /**
     * Check if the current user can manage forms.
     */
    public static function canManageForms(): bool
    {
        return self::currentUserCan( self::MANAGE_FORMS );
    }

    /**
     * Check if the current user can manage plugin settings.
     */
    public static function canManageSettings(): bool
    {
        return self::currentUserCan( self::MANAGE_SETTINGS );
    }
}
