<?php

declare(strict_types=1);

namespace NextPressBuilder\Admin;

use NextPressBuilder\Core\Capability;

/**
 * Registers the top-level "NextPress" menu in wp-admin.
 *
 * Each sub-page renders a React mount point that the admin SPA takes over.
 */
class AdminMenu
{
    private const MENU_SLUG = 'nextpress';
    private const ICON = 'dashicons-layout';
    private const POSITION = 30; // After "Comments" in the sidebar.

    /**
     * Register all admin menu pages.
     * Called on the 'admin_menu' action.
     */
    public function register(): void
    {
        // Top-level menu.
        add_menu_page(
            __( 'NextPress Builder', 'nextpress-builder' ),
            __( 'NextPress', 'nextpress-builder' ),
            Capability::EDIT_PAGES,
            self::MENU_SLUG,
            [ $this, 'renderApp' ],
            self::ICON,
            self::POSITION
        );

        // Sub-pages (all rendered by the same React SPA, routed client-side).
        $subPages = $this->getSubPages();

        foreach ( $subPages as $page ) {
            add_submenu_page(
                self::MENU_SLUG,
                $page['title'],
                $page['menu_title'],
                $page['capability'],
                $page['slug'],
                [ $this, 'renderApp' ]
            );
        }
    }

    /**
     * Render the admin page.
     *
     * Always outputs the #npb-admin-root div. The PHP dashboard renders inside it
     * as server-side HTML. On pages where the React SPA is active (controlled by
     * index.tsx), React mounts into this div and replaces the PHP content.
     * On all other pages, the PHP dashboard remains visible.
     */
    public function renderApp(): void
    {
        echo '<div id="npb-admin-root" class="npb-admin-wrap">';
        $this->renderPhpDashboard();
        echo '</div>';
    }

    /**
     * Temporary PHP-rendered dashboard.
     * Replaced by React SPA once admin/build/index.js is compiled.
     */
    private function renderPhpDashboard(): void
    {
        $currentPage = sanitize_text_field( wp_unslash( $_GET['page'] ?? 'nextpress' ) );

        $plugin   = \NextPressBuilder\Plugin::instance();
        $modules  = $plugin->getModuleManager()->all();
        $settings = $plugin->make( \NextPressBuilder\Core\SettingsManager::class );

        $dbVersion   = $settings->getString( 'db_version', '0' );
        $nextjsUrl   = $settings->getString( 'nextjs_frontend_url', '' );
        $revalSecret = $settings->getString( 'nextjs_revalidation_secret', '' );
        $installedAt = $settings->getString( 'installed_at', '' );

        // Live data from DB.
        global $wpdb;
        $prefix = $wpdb->prefix . 'npb_';
        $tables = [];
        $tableNames = ['pages','sections','components','style_variants','forms','form_submissions','themes','buttons','navigation_menus','templates'];
        foreach ( $tableNames as $t ) {
            $full = $prefix . $t;
            $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $full ) ) === $full;
            $count = $exists ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$full}" ) : 0;
            $tables[$t] = ['exists' => $exists, 'count' => $count];
        }

        // Theme data.
        $themeRepo = new \NextPressBuilder\Core\Repository\ThemeRepository();
        $allThemes = $themeRepo->findBy([], 'name', 'ASC');
        $activeTheme = $themeRepo->findActive();

        // API endpoints list.
        $apiBase = rest_url( 'npb/v1' );

        ?>
        <style>
            .npb *{box-sizing:border-box;margin:0;padding:0}
            .npb{max-width:1380px;margin:0 auto;padding:20px 20px 40px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;-webkit-font-smoothing:antialiased;color:#1e293b}

            /* ── Header ── */
            .npb-h{display:flex;align-items:center;justify-content:space-between;gap:24px;background:linear-gradient(135deg,#0a0a0a 0%,#18181b 50%,#0a0a0a 100%);border-radius:14px;padding:28px 32px;margin-bottom:24px;border:1px solid #27272a;box-shadow:0 1px 3px rgba(0,0,0,.3),0 8px 32px rgba(0,0,0,.25),inset 0 1px 0 rgba(255,255,255,.04)}
            .npb-h-left{display:flex;align-items:center;gap:18px}
            .npb-h-logo{width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#f97316,#ef4444);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(249,115,22,.35);flex-shrink:0}
            .npb-h-logo svg{width:26px;height:26px;color:#fff}
            .npb-h-title{font-size:22px;font-weight:800;color:#fafafa;letter-spacing:-.03em;line-height:1.2}
            .npb-h-title span{color:#f97316}
            .npb-h-sub{font-size:13px;color:#71717a;font-weight:400;margin-top:2px}
            .npb-h-right{display:flex;align-items:center;gap:10px;flex-shrink:0}
            .npb-h-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:100px;font-size:11px;font-weight:600;border:1px solid #27272a;color:#a1a1aa;background:#18181b;letter-spacing:.01em}
            .npb-h-pill b{width:6px;height:6px;border-radius:50%;display:inline-block}
            .npb-h-pill .g{background:#22c55e;box-shadow:0 0 8px rgba(34,197,94,.6)}
            .npb-h-pill .b{background:#3b82f6;box-shadow:0 0 8px rgba(59,130,246,.5)}
            .npb-h-pill .o{background:#f97316;box-shadow:0 0 8px rgba(249,115,22,.5)}

            /* ── Section Header ── */
            .npb-sh{font-size:13px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin:0 0 14px;display:flex;align-items:center;gap:10px}
            .npb-sh::after{content:'';flex:1;height:1px;background:#e2e8f0}

            /* ── Stat Grid ── */
            .npb-sg{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px}
            @media(max-width:1100px){.npb-sg{grid-template-columns:repeat(2,1fr)}}
            .npb-sc{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;box-shadow:0 1px 2px rgba(0,0,0,.04),0 2px 8px rgba(0,0,0,.02);transition:all .15s ease}
            .npb-sc:hover{border-color:#d4d4d8;box-shadow:0 2px 4px rgba(0,0,0,.06),0 8px 24px rgba(0,0,0,.06);transform:translateY(-1px)}
            .npb-sc-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
            .npb-sc-ico{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center}
            .npb-sc-ico svg{width:18px;height:18px}
            .npb-ico-g{background:#f0fdf4;color:#16a34a}
            .npb-ico-v{background:#faf5ff;color:#9333ea}
            .npb-ico-b{background:#eff6ff;color:#2563eb}
            .npb-ico-o{background:#fff7ed;color:#ea580c}
            .npb-tag{font-size:10px;font-weight:700;padding:3px 9px;border-radius:100px;text-transform:uppercase;letter-spacing:.04em}
            .npb-tag-g{background:#dcfce7;color:#15803d}
            .npb-tag-y{background:#fef9c3;color:#a16207}
            .npb-sc-val{font-size:26px;font-weight:800;color:#09090b;letter-spacing:-.03em;line-height:1}
            .npb-sc-lbl{font-size:12px;color:#6b7280;font-weight:500;margin-top:4px}
            .npb-sc-ft{margin-top:14px;padding-top:10px;border-top:1px solid #f3f4f6;font-size:11px;color:#9ca3af}
            .npb-sc-ft code{background:#f4f4f5;color:#3f3f46;padding:2px 6px;border-radius:4px;font-size:10px;font-family:ui-monospace,SFMono-Regular,monospace}
            .npb-sc-ft a{color:#7c3aed;text-decoration:none;font-weight:600}
            .npb-sc-ft a:hover{color:#6d28d9;text-decoration:underline}
            .npb-caps{display:grid;grid-template-columns:1fr 1fr;gap:4px;margin-top:6px}
            .npb-cap{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:500;color:#52525b;font-family:ui-monospace,SFMono-Regular,monospace;padding:3px 0}
            .npb-cap i{width:5px;height:5px;border-radius:50%;background:#22c55e;flex-shrink:0;display:inline-block}

            /* ── Quick Nav ── */
            .npb-qn{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:28px}
            @media(max-width:1100px){.npb-qn{grid-template-columns:repeat(3,1fr)}}
            .npb-qc{display:flex;align-items:center;gap:12px;padding:14px 16px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;text-decoration:none;color:#374151;transition:all .2s;box-shadow:0 1px 2px rgba(0,0,0,.03)}
            .npb-qc:hover{border-color:#c084fc;background:#faf5ff;color:#7c3aed;box-shadow:0 4px 16px rgba(124,58,237,.1);transform:translateY(-2px)}
            .npb-qc:active{transform:translateY(0)}
            .npb-qi{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s}
            .npb-qc:hover .npb-qi{transform:scale(1.05)}
            .npb-qi .dashicons{font-size:18px;width:18px;height:18px}
            .npb-ql{font-size:13px;font-weight:600;letter-spacing:-.01em}

            /* ── Roadmap ── */
            .npb-rm{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;box-shadow:0 1px 2px rgba(0,0,0,.04),0 2px 8px rgba(0,0,0,.02);margin-bottom:28px}
            .npb-rm-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
            .npb-rm-t{font-size:14px;font-weight:700;color:#09090b}
            .npb-rm-pct{font-size:12px;font-weight:700;color:#7c3aed}
            .npb-pb{height:5px;background:#f4f4f5;border-radius:100px;overflow:hidden;margin-bottom:20px}
            .npb-pf{height:100%;border-radius:100px;background:linear-gradient(90deg,#7c3aed,#c084fc);box-shadow:0 0 12px rgba(124,58,237,.3)}
            .npb-sl{list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:6px 20px}
            @media(max-width:900px){.npb-sl{grid-template-columns:1fr}}
            .npb-si{display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:8px;transition:background .1s}
            .npb-si:hover{background:#fafafa}
            .npb-sd{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:10px;font-weight:800}
            .npb-si-d .npb-sd{background:#7c3aed;color:#fff;box-shadow:0 2px 8px rgba(124,58,237,.3)}
            .npb-si-a .npb-sd{background:#fff;border:2px solid #7c3aed;color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.12)}
            .npb-si-p .npb-sd{background:#f4f4f5;color:#a1a1aa;border:1.5px solid #e4e4e7}
            .npb-sn{font-size:12px;font-weight:600;color:#18181b}
            .npb-si-p .npb-sn{color:#a1a1aa}
            .npb-st{font-size:11px;color:#a1a1aa;font-weight:400;margin-left:4px}
            .npb-si-d .npb-st{color:#71717a}

            /* ── Coming Soon ── */
            .npb-cs{text-align:center;padding:72px 24px;background:#fff;border:2px dashed #e4e4e7;border-radius:14px}
            .npb-cs-i{width:56px;height:56px;margin:0 auto 16px;background:#f4f4f5;border-radius:14px;display:flex;align-items:center;justify-content:center}
            .npb-cs-i .dashicons{font-size:24px;width:24px;height:24px;color:#a1a1aa}
            .npb-cs h3{font-size:16px;font-weight:700;color:#27272a;margin-bottom:6px}
            .npb-cs p{font-size:13px;color:#a1a1aa;line-height:1.6}
        </style>

        <div class="npb">

            <!-- Header -->
            <div class="npb-h">
                <div class="npb-h-left">
                    <div class="npb-h-logo">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    </div>
                    <div>
                        <div class="npb-h-title">NextPress <span>Builder</span></div>
                        <div class="npb-h-sub">Headless WordPress + Next.js Page Builder</div>
                    </div>
                </div>
                <div class="npb-h-right">
                    <span class="npb-h-pill"><b class="g"></b> v<?php echo esc_html( NPB_VERSION ); ?></span>
                    <span class="npb-h-pill"><b class="b"></b> PHP <?php echo esc_html( PHP_VERSION ); ?></span>
                    <span class="npb-h-pill"><b class="o"></b> WP <?php echo esc_html( get_bloginfo( 'version' ) ); ?></span>
                </div>
            </div>

            <?php if ( $currentPage === 'nextpress' ) : ?>

            <!-- Two-column layout -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">

                <!-- Database Tables -->
                <div class="npb-sc" style="padding:0;overflow:hidden">
                    <div style="padding:16px 18px 12px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between">
                        <div style="font-size:13px;font-weight:700;color:#09090b">Database Tables</div>
                        <code style="background:#f0fdf4;color:#16a34a;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700">v<?php echo esc_html($dbVersion); ?></code>
                    </div>
                    <div style="padding:4px 0">
                        <?php foreach ($tables as $name => $info) :
                            $ok = $info['exists'];
                        ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 18px;font-size:12px;<?php echo !$ok ? 'opacity:.5' : ''; ?>">
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="width:6px;height:6px;border-radius:50%;background:<?php echo $ok ? '#22c55e' : '#ef4444'; ?>;display:inline-block"></span>
                                <code style="color:#374151;font-size:11px"><?php echo esc_html($prefix . $name); ?></code>
                            </div>
                            <span style="color:#6b7280;font-weight:600;font-size:11px"><?php echo $ok ? $info['count'] . ' rows' : 'missing'; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- API Endpoints (clickable) -->
                <div class="npb-sc" style="padding:0;overflow:hidden">
                    <div style="padding:16px 18px 12px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between">
                        <div style="font-size:13px;font-weight:700;color:#09090b">API Endpoints</div>
                        <code style="background:#eff6ff;color:#2563eb;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700">18 routes</code>
                    </div>
                    <div style="padding:4px 0">
                        <?php
                        $endpoints = [
                            ['GET', '/health', true],
                            ['GET', '/site-config', true],
                            ['GET', '/theme', true],
                            ['GET', '/theme/css-variables', true],
                            ['GET', '/theme/fonts', true],
                            ['GET', '/themes', false],
                            ['POST', '/themes', false],
                            ['PUT', '/themes/{id}', false],
                            ['PUT', '/themes/{id}/activate', false],
                            ['DELETE', '/themes/{id}', false],
                            ['GET', '/themes/{id}/export', false],
                            ['POST', '/themes/import', false],
                            ['POST', '/auth/token', true],
                            ['GET', '/auth/me', false],
                            ['GET', '/settings', false],
                            ['PUT', '/settings', false],
                        ];
                        foreach ($endpoints as $ep) :
                            $method = $ep[0];
                            $path = $ep[1];
                            $isPublic = $ep[2];
                            $isClickable = $method === 'GET' && !str_contains($path, '{');
                            $methodColor = match($method) {
                                'GET' => '#16a34a', 'POST' => '#2563eb', 'PUT' => '#d97706', 'DELETE' => '#dc2626', default => '#6b7280'
                            };
                        ?>
                        <div style="display:flex;align-items:center;gap:8px;padding:5px 18px;font-size:12px">
                            <code style="background:<?php echo $methodColor; ?>;color:#fff;padding:1px 6px;border-radius:3px;font-size:9px;font-weight:700;min-width:36px;text-align:center"><?php echo $method; ?></code>
                            <?php if ($isClickable) : ?>
                                <a href="<?php echo esc_url(rest_url('npb/v1' . $path)); ?>" target="_blank" style="color:#374151;text-decoration:none;font-family:ui-monospace,monospace;font-size:11px;flex:1"><?php echo esc_html($path); ?></a>
                            <?php else : ?>
                                <code style="color:#374151;font-size:11px;flex:1"><?php echo esc_html($path); ?></code>
                            <?php endif; ?>
                            <span style="font-size:9px;padding:1px 6px;border-radius:3px;font-weight:600;<?php echo $isPublic ? 'background:#f0fdf4;color:#16a34a' : 'background:#fef3c7;color:#92400e'; ?>"><?php echo $isPublic ? 'public' : 'auth'; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Themes + Active Theme Preview -->
            <p class="npb-sh">Themes (<?php echo count($allThemes); ?> installed)</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">

                <!-- Theme List -->
                <div class="npb-sc" style="padding:0;overflow:hidden">
                    <div style="padding:16px 18px 12px;border-bottom:1px solid #f3f4f6;font-size:13px;font-weight:700;color:#09090b">All Themes</div>
                    <div style="padding:6px 0">
                        <?php foreach ($allThemes as $t) :
                            $isAct = !empty($t->is_active);
                            $colors = is_object($t->colors) ? (array) $t->colors : [];
                            $pColor = is_object($colors['primary'] ?? null) ? ($colors['primary']->value ?? '#666') : ($colors['primary'] ?? '#666');
                            $aColor = is_object($colors['accent'] ?? null) ? ($colors['accent']->value ?? '#999') : ($colors['accent'] ?? '#999');
                            $heading = is_object($t->typography) && isset($t->typography->heading) ? ($t->typography->heading->family ?? 'Inter') : 'Inter';
                        ?>
                        <div style="display:flex;align-items:center;gap:12px;padding:8px 18px;<?php echo $isAct ? 'background:#f0fdf4;' : ''; ?>">
                            <div style="display:flex;gap:3px;flex-shrink:0">
                                <span style="width:18px;height:18px;border-radius:4px;background:<?php echo esc_attr($pColor); ?>;display:inline-block;border:1px solid rgba(0,0,0,.1)"></span>
                                <span style="width:18px;height:18px;border-radius:4px;background:<?php echo esc_attr($aColor); ?>;display:inline-block;border:1px solid rgba(0,0,0,.1)"></span>
                            </div>
                            <div style="flex:1;min-width:0">
                                <div style="font-size:13px;font-weight:600;color:#09090b"><?php echo esc_html($t->name); ?></div>
                                <div style="font-size:10px;color:#9ca3af"><?php echo esc_html($heading); ?></div>
                            </div>
                            <?php if ($isAct) : ?>
                                <span style="font-size:9px;font-weight:700;background:#16a34a;color:#fff;padding:2px 8px;border-radius:100px">ACTIVE</span>
                            <?php else : ?>
                                <span style="font-size:10px;color:#a1a1aa"><?php echo esc_html($t->slug); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Active Theme CSS Preview -->
                <div class="npb-sc" style="padding:0;overflow:hidden">
                    <div style="padding:16px 18px 12px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between">
                        <div style="font-size:13px;font-weight:700;color:#09090b">Active Theme: <?php echo esc_html($activeTheme->name ?? 'None'); ?></div>
                        <a href="<?php echo esc_url(rest_url('npb/v1/theme/css-variables')); ?>" target="_blank" style="font-size:10px;color:#7c3aed;text-decoration:none;font-weight:600">View CSS &rarr;</a>
                    </div>
                    <?php if ($activeTheme) :
                        $aColors = is_object($activeTheme->colors) ? (array) $activeTheme->colors : [];
                    ?>
                    <div style="padding:14px 18px">
                        <!-- Color swatches -->
                        <div style="font-size:10px;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Colors</div>
                        <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:16px">
                            <?php
                            $showColors = ['primary','primary-dark','primary-light','accent','accent-dark','dark','secondary','success','warning','error','info'];
                            foreach ($showColors as $cName) :
                                $cVal = $aColors[$cName] ?? null;
                                $hex = is_object($cVal) ? ($cVal->value ?? '') : (is_string($cVal) ? $cVal : '');
                                if (!$hex) continue;
                            ?>
                                <div style="text-align:center" title="<?php echo esc_attr("--color-{$cName}: {$hex}"); ?>">
                                    <div style="width:32px;height:32px;border-radius:6px;background:<?php echo esc_attr($hex); ?>;border:1px solid rgba(0,0,0,.08)"></div>
                                    <div style="font-size:8px;color:#a1a1aa;margin-top:2px"><?php echo esc_html($cName); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Typography -->
                        <div style="font-size:10px;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Typography</div>
                        <?php if (isset($activeTheme->typography)) :
                            $typo = $activeTheme->typography;
                        ?>
                        <div style="display:flex;gap:16px;margin-bottom:16px">
                            <?php foreach (['heading','body','mono'] as $role) :
                                $f = $typo->{$role} ?? null;
                                if (!$f) continue;
                            ?>
                            <div style="flex:1;background:#f9fafb;border-radius:6px;padding:8px 10px">
                                <div style="font-size:10px;color:#6b7280;text-transform:capitalize"><?php echo $role; ?></div>
                                <div style="font-size:13px;font-weight:700;color:#09090b"><?php echo esc_html($f->family ?? ''); ?></div>
                                <div style="font-size:9px;color:#a1a1aa">Weights: <?php echo esc_html(implode(', ', (array)($f->weights ?? []))); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Spacing -->
                        <div style="font-size:10px;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Spacing</div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <?php if (isset($activeTheme->spacing)) :
                                $sp = $activeTheme->spacing;
                            ?>
                                <code style="background:#f4f4f5;padding:2px 8px;border-radius:4px;font-size:10px;color:#3f3f46">base: <?php echo esc_html($sp->base ?? '4'); ?>px</code>
                                <code style="background:#f4f4f5;padding:2px 8px;border-radius:4px;font-size:10px;color:#3f3f46">max-w: <?php echo esc_html($sp->containerMaxWidth ?? '1280px'); ?></code>
                            <?php endif; ?>
                            <?php if (isset($activeTheme->borders)) :
                                $bd = (array)$activeTheme->borders;
                            ?>
                                <code style="background:#f4f4f5;padding:2px 8px;border-radius:4px;font-size:10px;color:#3f3f46">radius-md: <?php echo esc_html($bd['md'] ?? '8px'); ?></code>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Modules + Quick Nav -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">

                <!-- Loaded Modules -->
                <div class="npb-sc" style="padding:0;overflow:hidden">
                    <div style="padding:16px 18px 12px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between">
                        <div style="font-size:13px;font-weight:700;color:#09090b">Loaded Modules</div>
                        <code style="background:#faf5ff;color:#7c3aed;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700"><?php echo count($modules); ?> active</code>
                    </div>
                    <?php if (empty($modules)) : ?>
                        <div style="padding:24px 18px;text-align:center;color:#a1a1aa;font-size:12px">No modules loaded yet</div>
                    <?php else : ?>
                        <div style="padding:6px 0">
                        <?php foreach ($modules as $slug => $mod) : ?>
                            <div style="display:flex;align-items:center;gap:10px;padding:8px 18px">
                                <span style="width:8px;height:8px;border-radius:50%;background:#22c55e;box-shadow:0 0 6px rgba(34,197,94,.4);flex-shrink:0"></span>
                                <div style="flex:1">
                                    <div style="font-size:13px;font-weight:600;color:#09090b"><?php echo esc_html($mod->name()); ?></div>
                                    <div style="font-size:10px;color:#a1a1aa"><?php echo esc_html($slug); ?> &middot; v<?php echo esc_html($mod->version()); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Nav -->
                <div class="npb-sc" style="padding:0;overflow:hidden">
                    <div style="padding:16px 18px 12px;border-bottom:1px solid #f3f4f6;font-size:13px;font-weight:700;color:#09090b">Quick Access</div>
                    <div style="padding:6px 0">
                    <?php
                    $nav = [
                        ['nextpress-pages','Pages','dashicons-text-page','#f0fdf4','#16a34a'],
                        ['nextpress-theme','Theme','dashicons-art','#faf5ff','#7c3aed'],
                        ['nextpress-forms','Forms','dashicons-feedback','#fef2f2','#dc2626'],
                        ['nextpress-components','Components','dashicons-grid-view','#fff7ed','#ea580c'],
                        ['nextpress-templates','Templates','dashicons-layout','#fefce8','#ca8a04'],
                        ['nextpress-settings','Settings','dashicons-admin-generic','#f5f3ff','#7c3aed'],
                    ];
                    foreach ($nav as $n) :
                    ?>
                        <a href="<?php echo esc_url(admin_url("admin.php?page={$n[0]}")); ?>" style="display:flex;align-items:center;gap:10px;padding:8px 18px;text-decoration:none;color:#374151;transition:background .1s">
                            <div style="width:28px;height:28px;border-radius:7px;background:<?php echo $n[3]; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <span class="dashicons <?php echo esc_attr($n[2]); ?>" style="font-size:14px;width:14px;height:14px;color:<?php echo $n[4]; ?>"></span>
                            </div>
                            <span style="font-size:13px;font-weight:500"><?php echo esc_html($n[1]); ?></span>
                        </a>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Roadmap (compact) -->
            <p class="npb-sh">Build Roadmap</p>
            <div class="npb-rm">
                <div class="npb-rm-top">
                    <span class="npb-rm-t">Development Progress</span>
                    <span class="npb-rm-pct">5 / 18 sessions</span>
                </div>
                <div class="npb-pb"><div class="npb-pf" style="width:28%"></div></div>
                <ul class="npb-sl">
                    <?php
                    $steps = [
                        ['d','01','Plan Review','Architecture + features'],
                        ['d','02','Plugin Scaffold','17 core classes built'],
                        ['d','03','Database Layer','10 tables + repositories'],
                        ['d','04','REST API Auth','Rate limit + JWT + cache'],
                        ['d','05','Theme Manager','10 themes + CSS vars'],
                        ['a','06','Components','20 types, 400 variants'],
                        ['p','07','Page Builder','CRUD + sections + nesting'],
                        ['p','08','Layout System','Flex/grid containers'],
                        ['p','09','React Admin','SPA replaces this UI'],
                        ['p','10','Drag & Drop','Visual builder + preview'],
                    ];
                    foreach ($steps as $s) :
                        $cls = $s[0] === 'd' ? 'npb-si-d' : ($s[0] === 'a' ? 'npb-si-a' : 'npb-si-p');
                    ?>
                        <li class="npb-si <?php echo $cls; ?>">
                            <div class="npb-sd"><?php if ($s[0]==='d') : ?><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><?php else: echo $s[1]; endif; ?></div>
                            <div><span class="npb-sn"><?php echo esc_html($s[2]); ?></span><span class="npb-st"> &mdash; <?php echo esc_html($s[3]); ?></span></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php elseif ( $currentPage === 'nextpress-settings' ) : ?>
                <?php $this->renderSettingsPage($settings); ?>

            <?php elseif ( $currentPage === 'nextpress-theme' ) : ?>
                <?php $this->renderThemePage($allThemes, $activeTheme); ?>

            <?php elseif ( $currentPage === 'nextpress-components' ) : ?>
                <!-- React-powered Components page -->
                <div id="npb-admin-root"></div>

            <?php elseif ( $currentPage === 'nextpress-navigation' ) : ?>
                <?php $this->renderNavigationPage(); ?>

            <?php elseif ( $currentPage === 'nextpress-forms' ) : ?>
                <?php $this->renderFormsPage(); ?>

            <?php elseif ( $currentPage === 'nextpress-pages' ) : ?>
                <?php $this->renderPagesPage(); ?>

            <?php elseif ( $currentPage === 'nextpress-templates' ) : ?>
                <?php $this->renderTemplatesPage(); ?>

            <?php else :
                // Show info page for modules not yet built.
                $pageInfo = [
                    'nextpress-headers'    => ['Headers','dashicons-align-center','Create and manage header layouts (standard, centered, transparent, etc.).','Session 11: Header/Footer/Nav'],
                    'nextpress-footers'    => ['Footers','dashicons-align-none','Create and manage footer layouts with columns and widgets.','Session 11: Header/Footer/Nav'],
                    'nextpress-forms'      => ['Forms','dashicons-feedback','Build forms with 15 field types, multi-step, conditional logic, spam protection.','Session 13: Form Builder'],
                    'nextpress-components' => ['Components','dashicons-grid-view','Browse 20 section types with 400 style variants.','Session 06: Component Library'],
                    'nextpress-templates'  => ['Templates','dashicons-layout','Import pre-built business website templates with one click.','Session 15: Template Library'],
                    'nextpress-seo'        => ['SEO','dashicons-search','Per-page SEO, sitemap, redirects, social preview.','Session 14: SEO + Schema'],
                    'nextpress-navigation' => ['Navigation','dashicons-menu-alt3','Build nested menus with drag-and-drop. Multiple locations.','Session 11: Header/Footer/Nav'],
                ];
                $info = $pageInfo[$currentPage] ?? ['Module','dashicons-admin-generic','This module is coming soon.','Future session'];
            ?>
            <div class="npb-sc" style="padding:0;overflow:hidden">
                <div style="padding:48px 32px;text-align:center">
                    <div style="width:56px;height:56px;margin:0 auto 16px;background:#f4f4f5;border-radius:14px;display:flex;align-items:center;justify-content:center">
                        <span class="dashicons <?php echo esc_attr($info[1]); ?>" style="font-size:24px;width:24px;height:24px;color:#71717a"></span>
                    </div>
                    <h3 style="font-size:18px;font-weight:700;color:#09090b;margin-bottom:6px"><?php echo esc_html($info[0]); ?></h3>
                    <p style="font-size:14px;color:#71717a;margin-bottom:4px;line-height:1.5"><?php echo esc_html($info[2]); ?></p>
                    <p style="font-size:12px;color:#a1a1aa">Coming in: <strong><?php echo esc_html($info[3]); ?></strong></p>

                    <?php
                    // Show relevant API endpoints that are already working.
                    $pageApis = [
                        'nextpress-pages'   => [['GET','/pages'],['POST','/pages'],['GET','/pages/{slug}']],
                        'nextpress-forms'   => [['GET','/forms/{slug}'],['POST','/forms/{slug}/submit']],
                        'nextpress-seo'     => [['GET','/seo/global'],['GET','/seo/page/{slug}'],['GET','/seo/sitemap']],
                    ];
                    if (isset($pageApis[$currentPage])) :
                    ?>
                    <div style="margin-top:20px;padding-top:20px;border-top:1px solid #f3f4f6">
                        <div style="font-size:10px;color:#a1a1aa;text-transform:uppercase;font-weight:600;letter-spacing:.05em;margin-bottom:8px">Planned API Endpoints</div>
                        <?php foreach ($pageApis[$currentPage] as $ep) :
                            $mc = $ep[0]==='GET' ? '#16a34a' : ($ep[0]==='POST' ? '#2563eb' : '#d97706');
                        ?>
                        <div style="display:inline-flex;align-items:center;gap:4px;margin:2px 4px;font-size:11px">
                            <code style="background:<?php echo $mc; ?>;color:#fff;padding:1px 5px;border-radius:3px;font-size:9px;font-weight:700"><?php echo $ep[0]; ?></code>
                            <code style="color:#52525b;font-size:10px"><?php echo esc_html($ep[1]); ?></code>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
        <?php
    }

    /**
     * Render the Settings page — fully functional with save.
     */
    private function renderSettingsPage(\NextPressBuilder\Core\SettingsManager $settings): void
    {
        // Handle form save.
        if ( isset($_POST['npb_save_settings']) && check_admin_referer('npb_settings_nonce') ) {
            $fields = [
                'nextjs_frontend_url','nextjs_revalidation_url','nextjs_revalidation_secret',
                'business_name','business_phone','business_email','business_address',
                'business_city','business_state','business_zip','business_country',
                'business_latitude','business_longitude',
            ];
            foreach ($fields as $f) {
                if (isset($_POST[$f])) {
                    $settings->set($f, sanitize_text_field(wp_unslash($_POST[$f])));
                }
            }
            echo '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#166534;font-weight:500">Settings saved successfully.</div>';
        }

        $fieldGroups = [
            'Next.js Connection' => [
                ['nextjs_frontend_url', 'Frontend URL', 'url', 'https://your-site.vercel.app'],
                ['nextjs_revalidation_url', 'Revalidation URL', 'url', 'https://your-site.vercel.app/api/revalidate'],
                ['nextjs_revalidation_secret', 'Revalidation Secret', 'text', 'Auto-generated secret key'],
            ],
            'Business Information' => [
                ['business_name', 'Business Name', 'text', 'Your Company Name'],
                ['business_phone', 'Phone', 'tel', '+1 (555) 123-4567'],
                ['business_email', 'Email', 'email', 'contact@example.com'],
                ['business_address', 'Address', 'text', '123 Main St'],
                ['business_city', 'City', 'text', 'Austin'],
                ['business_state', 'State', 'text', 'TX'],
                ['business_zip', 'ZIP Code', 'text', '78701'],
                ['business_country', 'Country', 'text', 'US'],
                ['business_latitude', 'Latitude', 'text', '30.2672'],
                ['business_longitude', 'Longitude', 'text', '-97.7431'],
            ],
        ];
        ?>
        <form method="post">
            <?php wp_nonce_field('npb_settings_nonce'); ?>

            <?php foreach ($fieldGroups as $groupName => $fields) : ?>
            <p class="npb-sh"><?php echo esc_html($groupName); ?></p>
            <div class="npb-sc" style="padding:0;overflow:hidden;margin-bottom:20px">
                <div style="padding:4px 0">
                    <?php foreach ($fields as $field) :
                        $value = $settings->getString($field[0]);
                    ?>
                    <div style="display:flex;align-items:center;gap:12px;padding:10px 20px;border-bottom:1px solid #f9fafb">
                        <label style="width:180px;font-size:12px;font-weight:600;color:#374151;flex-shrink:0" for="<?php echo esc_attr($field[0]); ?>"><?php echo esc_html($field[1]); ?></label>
                        <input
                            type="<?php echo esc_attr($field[2]); ?>"
                            id="<?php echo esc_attr($field[0]); ?>"
                            name="<?php echo esc_attr($field[0]); ?>"
                            value="<?php echo esc_attr($value); ?>"
                            placeholder="<?php echo esc_attr($field[3]); ?>"
                            style="flex:1;padding:8px 12px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px;color:#09090b;background:#fff;outline:none;transition:border-color .15s"
                            onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e5e7eb'"
                        />
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- API Info -->
            <p class="npb-sh">API Information (Read Only)</p>
            <div class="npb-sc" style="padding:0;overflow:hidden;margin-bottom:20px">
                <div style="padding:4px 0">
                    <div style="display:flex;align-items:center;gap:12px;padding:10px 20px;border-bottom:1px solid #f9fafb">
                        <span style="width:180px;font-size:12px;font-weight:600;color:#374151;flex-shrink:0">API Base URL</span>
                        <code style="flex:1;padding:8px 12px;border:1px solid #f3f4f6;border-radius:6px;font-size:12px;color:#6b7280;background:#f9fafb"><?php echo esc_html(rest_url('npb/v1')); ?></code>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;padding:10px 20px;border-bottom:1px solid #f9fafb">
                        <span style="width:180px;font-size:12px;font-weight:600;color:#374151;flex-shrink:0">DB Version</span>
                        <code style="padding:8px 12px;border:1px solid #f3f4f6;border-radius:6px;font-size:12px;color:#6b7280;background:#f9fafb"><?php echo esc_html($settings->getString('db_version', '0')); ?></code>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;padding:10px 20px">
                        <span style="width:180px;font-size:12px;font-weight:600;color:#374151;flex-shrink:0">Installed</span>
                        <code style="padding:8px 12px;border:1px solid #f3f4f6;border-radius:6px;font-size:12px;color:#6b7280;background:#f9fafb"><?php echo esc_html($settings->getString('installed_at')); ?></code>
                    </div>
                </div>
            </div>

            <button type="submit" name="npb_save_settings" value="1"
                style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;padding:10px 28px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(124,58,237,.3);transition:all .15s"
                onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 16px rgba(124,58,237,.4)'"
                onmouseout="this.style.transform='';this.style.boxShadow='0 2px 8px rgba(124,58,237,.3)'"
            >Save Settings</button>
        </form>
        <?php
    }

    /**
     * Render the Theme page — view all themes, activate, preview colors.
     */
    private function renderThemePage(array $allThemes, ?object $activeTheme): void
    {
        // Handle theme activation.
        if ( isset($_GET['activate_theme']) && check_admin_referer('npb_activate_theme') ) {
            $themeId = (int) $_GET['activate_theme'];
            $repo = new \NextPressBuilder\Core\Repository\ThemeRepository();
            $repo->activate($themeId);
            // Refresh data.
            $activeTheme = $repo->findActive();
            $allThemes = $repo->findBy([], 'name', 'ASC');
            echo '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#166534;font-weight:500">Theme activated successfully.</div>';
        }
        ?>

        <!-- Active Theme Detail -->
        <?php if ($activeTheme) :
            $ac = is_object($activeTheme->colors) ? (array)$activeTheme->colors : [];
            $at = $activeTheme->typography ?? null;
        ?>
        <p class="npb-sh">Active Theme: <?php echo esc_html($activeTheme->name); ?></p>
        <div class="npb-sc" style="margin-bottom:20px">
            <!-- Colors Row -->
            <div style="margin-bottom:20px">
                <div style="font-size:11px;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">Color Palette</div>
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                    <?php
                    $colorKeys = ['primary','primary-dark','primary-light','accent','accent-dark','dark','secondary',
                        'gray-50','gray-100','gray-200','gray-300','gray-400','gray-500','gray-600','gray-700','gray-800','gray-900',
                        'success','warning','error','info','white','black'];
                    foreach ($colorKeys as $ck) :
                        $cv = $ac[$ck] ?? null;
                        $hex = is_object($cv) ? ($cv->value ?? '') : (is_string($cv) ? $cv : '');
                        if (!$hex) continue;
                        $isLight = in_array($ck, ['white','gray-50','gray-100','gray-200']);
                    ?>
                    <div style="text-align:center" title="--color-<?php echo esc_attr($ck); ?>: <?php echo esc_attr($hex); ?>">
                        <div style="width:40px;height:40px;border-radius:8px;background:<?php echo esc_attr($hex); ?>;border:1px solid rgba(0,0,0,.08);box-shadow:0 1px 3px rgba(0,0,0,.1)"></div>
                        <div style="font-size:8px;color:#71717a;margin-top:3px;max-width:40px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html($ck); ?></div>
                        <div style="font-size:8px;color:#a1a1aa"><?php echo esc_html($hex); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Typography Row -->
            <?php if ($at) : ?>
            <div style="margin-bottom:20px">
                <div style="font-size:11px;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">Typography</div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
                    <?php foreach (['heading','body','mono'] as $role) :
                        $f = $at->{$role} ?? null;
                        if (!$f) continue;
                    ?>
                    <div style="background:#f9fafb;border:1px solid #f3f4f6;border-radius:8px;padding:14px 16px">
                        <div style="font-size:10px;color:#9ca3af;text-transform:uppercase;font-weight:600;margin-bottom:4px"><?php echo $role; ?></div>
                        <div style="font-size:18px;font-weight:700;color:#09090b;margin-bottom:4px"><?php echo esc_html($f->family ?? ''); ?></div>
                        <div style="font-size:11px;color:#71717a">Weights: <?php echo esc_html(implode(', ', (array)($f->weights ?? []))); ?></div>
                        <div style="font-size:11px;color:#a1a1aa">Source: <?php echo esc_html($f->source ?? 'google'); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Links -->
            <div style="display:flex;gap:10px">
                <a href="<?php echo esc_url(rest_url('npb/v1/theme')); ?>" target="_blank"
                   style="font-size:12px;color:#7c3aed;text-decoration:none;font-weight:600;padding:6px 14px;background:#faf5ff;border-radius:6px;border:1px solid #e9d5ff">View JSON &rarr;</a>
                <a href="<?php echo esc_url(rest_url('npb/v1/theme/css-variables')); ?>" target="_blank"
                   style="font-size:12px;color:#2563eb;text-decoration:none;font-weight:600;padding:6px 14px;background:#eff6ff;border-radius:6px;border:1px solid #bfdbfe">View CSS Variables &rarr;</a>
                <a href="<?php echo esc_url(rest_url('npb/v1/theme/fonts')); ?>" target="_blank"
                   style="font-size:12px;color:#16a34a;text-decoration:none;font-weight:600;padding:6px 14px;background:#f0fdf4;border-radius:6px;border:1px solid #bbf7d0">View Fonts Config &rarr;</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- All Themes Grid -->
        <p class="npb-sh">All Themes (<?php echo count($allThemes); ?>)</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;margin-bottom:20px">
            <?php foreach ($allThemes as $t) :
                $isAct = !empty($t->is_active);
                $tc = is_object($t->colors) ? (array)$t->colors : [];
                $pHex = is_object($tc['primary'] ?? null) ? ($tc['primary']->value ?? '#666') : ($tc['primary'] ?? '#666');
                $aHex = is_object($tc['accent'] ?? null) ? ($tc['accent']->value ?? '#999') : ($tc['accent'] ?? '#999');
                $dHex = is_object($tc['dark'] ?? null) ? ($tc['dark']->value ?? '#222') : ($tc['dark'] ?? '#222');
                $sHex = is_object($tc['secondary'] ?? null) ? ($tc['secondary']->value ?? '#444') : ($tc['secondary'] ?? '#444');
                $tTypo = is_object($t->typography) && isset($t->typography->heading) ? ($t->typography->heading->family ?? 'Inter') : 'Inter';
                $activateUrl = wp_nonce_url(admin_url("admin.php?page=nextpress-theme&activate_theme={$t->id}"), 'npb_activate_theme');
            ?>
            <div class="npb-sc" style="padding:0;overflow:hidden;<?php echo $isAct ? 'border-color:#7c3aed;box-shadow:0 0 0 2px rgba(124,58,237,.15),0 2px 8px rgba(0,0,0,.06);' : ''; ?>">
                <!-- Color bar -->
                <div style="height:6px;background:linear-gradient(90deg,<?php echo esc_attr($pHex); ?>,<?php echo esc_attr($aHex); ?>)"></div>

                <div style="padding:16px 18px">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                        <div>
                            <div style="font-size:15px;font-weight:700;color:#09090b"><?php echo esc_html($t->name); ?></div>
                            <div style="font-size:11px;color:#a1a1aa"><?php echo esc_html($tTypo); ?> &middot; <?php echo esc_html($t->slug); ?></div>
                        </div>
                        <?php if ($isAct) : ?>
                            <span style="font-size:9px;font-weight:700;background:#7c3aed;color:#fff;padding:3px 10px;border-radius:100px">ACTIVE</span>
                        <?php endif; ?>
                    </div>

                    <!-- Color swatches -->
                    <div style="display:flex;gap:4px;margin-bottom:14px">
                        <?php foreach ([$pHex,$aHex,$dHex,$sHex] as $sw) : ?>
                            <div style="width:28px;height:28px;border-radius:6px;background:<?php echo esc_attr($sw); ?>;border:1px solid rgba(0,0,0,.06)"></div>
                        <?php endforeach; ?>
                        <div style="width:28px;height:28px;border-radius:6px;background:#f9fafb;border:1px solid #e5e7eb"></div>
                        <div style="width:28px;height:28px;border-radius:6px;background:#111827;border:1px solid rgba(0,0,0,.06)"></div>
                    </div>

                    <!-- Actions -->
                    <div style="display:flex;gap:8px">
                        <?php if (!$isAct) : ?>
                        <a href="<?php echo esc_url($activateUrl); ?>"
                           style="font-size:11px;font-weight:600;color:#7c3aed;text-decoration:none;padding:5px 14px;background:#faf5ff;border-radius:6px;border:1px solid #e9d5ff;transition:all .15s"
                           onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='#faf5ff'"
                        >Activate</a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url(rest_url("npb/v1/themes/{$t->id}/export")); ?>" target="_blank"
                           style="font-size:11px;font-weight:600;color:#6b7280;text-decoration:none;padding:5px 14px;background:#f9fafb;border-radius:6px;border:1px solid #e5e7eb">Export</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render the Components page — browse all 20 section types + variants.
     */
    private function renderComponentsPage(): void
    {
        $compRepo = new \NextPressBuilder\Core\Repository\ComponentRepository();
        $varRepo  = new \NextPressBuilder\Core\Repository\VariantRepository();

        $allComponents = $compRepo->findBy([], 'category', 'ASC');
        $categories = $compRepo->getCategories();

        // Filter by category if selected.
        $filterCat = sanitize_text_field(wp_unslash($_GET['cat'] ?? ''));

        // Category labels.
        $catLabels = [
            'hero'=>'Hero','features'=>'Features','social_proof'=>'Social Proof','cta'=>'CTA',
            'pricing'=>'Pricing','people'=>'People','faq'=>'FAQ','media'=>'Media','data'=>'Data',
            'contact'=>'Contact','history'=>'History','work'=>'Work','content'=>'Content',
            'about'=>'About','location'=>'Location','comparison'=>'Comparison','layout'=>'Layout',
        ];

        $catColors = [
            'hero'=>'#ef4444','features'=>'#f97316','social_proof'=>'#eab308','cta'=>'#22c55e',
            'pricing'=>'#14b8a6','people'=>'#06b6d4','faq'=>'#3b82f6','media'=>'#6366f1',
            'data'=>'#8b5cf6','contact'=>'#a855f7','history'=>'#d946ef','work'=>'#ec4899',
            'content'=>'#f43f5e','about'=>'#64748b','location'=>'#0ea5e9','comparison'=>'#10b981','layout'=>'#71717a',
        ];
        ?>

        <!-- Stats bar -->
        <div style="display:flex;gap:12px;margin-bottom:20px">
            <div class="npb-sc" style="padding:14px 20px;flex:1;display:flex;align-items:center;gap:12px">
                <div style="width:38px;height:38px;border-radius:10px;background:#fff7ed;display:flex;align-items:center;justify-content:center">
                    <span class="dashicons dashicons-grid-view" style="color:#ea580c;font-size:18px;width:18px;height:18px"></span>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:800;color:#09090b"><?php echo count($allComponents); ?></div>
                    <div style="font-size:11px;color:#71717a">Components</div>
                </div>
            </div>
            <div class="npb-sc" style="padding:14px 20px;flex:1;display:flex;align-items:center;gap:12px">
                <div style="width:38px;height:38px;border-radius:10px;background:#faf5ff;display:flex;align-items:center;justify-content:center">
                    <span class="dashicons dashicons-art" style="color:#7c3aed;font-size:18px;width:18px;height:18px"></span>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:800;color:#09090b"><?php echo $varRepo->count(); ?></div>
                    <div style="font-size:11px;color:#71717a">Style Variants</div>
                </div>
            </div>
            <div class="npb-sc" style="padding:14px 20px;flex:1;display:flex;align-items:center;gap:12px">
                <div style="width:38px;height:38px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center">
                    <span class="dashicons dashicons-category" style="color:#16a34a;font-size:18px;width:18px;height:18px"></span>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:800;color:#09090b"><?php echo count($categories); ?></div>
                    <div style="font-size:11px;color:#71717a">Categories</div>
                </div>
            </div>
        </div>

        <!-- Category filter tabs -->
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:20px">
            <a href="<?php echo esc_url(admin_url('admin.php?page=nextpress-components')); ?>"
               style="padding:6px 14px;border-radius:100px;font-size:11px;font-weight:600;text-decoration:none;<?php echo !$filterCat ? 'background:#09090b;color:#fff' : 'background:#f4f4f5;color:#52525b'; ?>">All (<?php echo count($allComponents); ?>)</a>
            <?php foreach ($categories as $cat) :
                $label = $catLabels[$cat] ?? ucfirst($cat);
                $color = $catColors[$cat] ?? '#71717a';
                $catCount = count(array_filter($allComponents, fn($c) => $c->category === $cat));
                $isActive = $filterCat === $cat;
            ?>
            <a href="<?php echo esc_url(admin_url("admin.php?page=nextpress-components&cat={$cat}")); ?>"
               style="padding:6px 14px;border-radius:100px;font-size:11px;font-weight:600;text-decoration:none;border:1px solid <?php echo $isActive ? $color : '#e4e4e7'; ?>;<?php echo $isActive ? "background:{$color};color:#fff" : "background:#fff;color:#52525b"; ?>"
            ><?php echo esc_html($label); ?> (<?php echo $catCount; ?>)</a>
            <?php endforeach; ?>
        </div>

        <!-- Components grid -->
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:14px">
            <?php foreach ($allComponents as $comp) :
                if ($filterCat && $comp->category !== $filterCat) continue;
                $cat = $comp->category ?? 'other';
                $color = $catColors[$cat] ?? '#71717a';
                $variants = $varRepo->findByComponent($comp->slug);
                $schema = is_object($comp->content_schema) ? $comp->content_schema : null;
                $fieldCount = $schema && isset($schema->fields) ? count((array)$schema->fields) : 0;
            ?>
            <div class="npb-sc" style="padding:0;overflow:hidden;transition:all .15s" onmouseover="this.style.borderColor='<?php echo $color; ?>40';this.style.boxShadow='0 4px 16px <?php echo $color; ?>12'" onmouseout="this.style.borderColor='#e5e7eb';this.style.boxShadow=''">
                <!-- Color accent bar -->
                <div style="height:3px;background:<?php echo esc_attr($color); ?>"></div>

                <div style="padding:18px 20px">
                    <!-- Header -->
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px">
                        <div>
                            <div style="font-size:15px;font-weight:700;color:#09090b;margin-bottom:2px"><?php echo esc_html($comp->name); ?></div>
                            <div style="font-size:11px;color:#a1a1aa">
                                <code style="background:<?php echo $color; ?>15;color:<?php echo $color; ?>;padding:1px 6px;border-radius:3px;font-size:9px;font-weight:700"><?php echo esc_html($catLabels[$cat] ?? $cat); ?></code>
                                &middot; <code style="color:#71717a;font-size:10px"><?php echo esc_html($comp->slug); ?></code>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <p style="font-size:12px;color:#71717a;line-height:1.5;margin-bottom:12px"><?php echo esc_html($comp->description ?? ''); ?></p>

                    <!-- Stats row -->
                    <div style="display:flex;gap:12px;margin-bottom:12px">
                        <div style="font-size:11px;color:#52525b"><strong style="color:#09090b"><?php echo $fieldCount; ?></strong> fields</div>
                        <div style="font-size:11px;color:#52525b"><strong style="color:#09090b"><?php echo count($variants); ?></strong> variants</div>
                        <div style="font-size:11px;color:#52525b"><?php echo empty($comp->is_user_created) ? '<span style="color:#16a34a">Built-in</span>' : '<span style="color:#7c3aed">Custom</span>'; ?></div>
                    </div>

                    <!-- Variant tags -->
                    <?php if (!empty($variants)) : ?>
                    <div style="display:flex;flex-wrap:wrap;gap:4px">
                        <?php foreach ($variants as $v) : ?>
                        <span style="font-size:9px;padding:2px 8px;border-radius:4px;background:#f4f4f5;color:#52525b;font-weight:500"
                              title="<?php echo esc_attr($v->variant_slug); ?>"><?php echo esc_html($v->name); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- API links -->
                    <div style="margin-top:12px;padding-top:10px;border-top:1px solid #f4f4f5;display:flex;gap:8px">
                        <a href="<?php echo esc_url(rest_url("npb/v1/components/{$comp->slug}")); ?>" target="_blank"
                           style="font-size:10px;color:#7c3aed;text-decoration:none;font-weight:600">Schema JSON &rarr;</a>
                        <a href="<?php echo esc_url(rest_url("npb/v1/components/{$comp->slug}/variants")); ?>" target="_blank"
                           style="font-size:10px;color:#2563eb;text-decoration:none;font-weight:600">Variants JSON &rarr;</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render the Navigation page — view menus + items.
     */
    /**
     * Render the Forms page — list forms, fields, submissions count.
     */
    /**
     * Render the Pages admin page — list pages, create, publish, manage sections.
     */
    /**
     * Render Templates page with import wizard.
     */
    private function renderTemplatesPage(): void
    {
        $tplRepo = new \NextPressBuilder\Core\Repository\TemplateRepository();

        // Handle import.
        if (isset($_POST['npb_import_template']) && check_admin_referer('npb_template_import')) {
            $slug = sanitize_text_field(wp_unslash($_POST['template_slug'] ?? ''));
            $template = $tplRepo->findBySlug($slug);

            if ($template) {
                $plugin = \NextPressBuilder\Plugin::instance();
                $importService = $plugin->make(\NextPressBuilder\Modules\TemplateLibrary\Service\TemplateImportService::class);

                $vars = [
                    'business_name' => sanitize_text_field(wp_unslash($_POST['biz_name'] ?? get_bloginfo('name'))),
                    'phone'         => sanitize_text_field(wp_unslash($_POST['biz_phone'] ?? '')),
                    'email'         => sanitize_email(wp_unslash($_POST['biz_email'] ?? get_option('admin_email'))),
                    'address'       => sanitize_text_field(wp_unslash($_POST['biz_address'] ?? '')),
                    'city'          => sanitize_text_field(wp_unslash($_POST['biz_city'] ?? '')),
                    'state'         => sanitize_text_field(wp_unslash($_POST['biz_state'] ?? '')),
                    'zip'           => sanitize_text_field(wp_unslash($_POST['biz_zip'] ?? '')),
                ];

                $templateData = json_decode(wp_json_encode($template->data ?? []), true) ?? [];
                $counts = $importService->import($templateData, $vars);

                echo '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px 20px;margin-bottom:20px;font-size:13px;color:#166534">';
                echo "<strong>Template imported!</strong> Created {$counts['pages']} pages, {$counts['sections']} sections, {$counts['forms']} forms.";
                echo ' <a href="' . esc_url(admin_url('admin.php?page=nextpress-pages')) . '" style="color:#166534;font-weight:700">View Pages &rarr;</a>';
                echo '</div>';
            }
        }

        $templates = $tplRepo->findBy([], 'name', 'ASC');
        $typeColors = ['plumber'=>'#2563eb','restaurant'=>'#ea580c','lawyer'=>'#7c3aed','dentist'=>'#0ea5e9','electrician'=>'#f59e0b'];
        ?>

        <p class="npb-sh">Template Library (<?php echo count($templates); ?> templates)</p>

        <?php if (empty($templates)) : ?>
            <div class="npb-sc" style="text-align:center;padding:40px;color:#a1a1aa;font-size:13px">
                No templates found. Templates are loaded from JSON fixtures on activation.
                Try deactivating and reactivating the plugin.
            </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(380px,1fr));gap:16px">
        <?php foreach ($templates as $tpl) :
            $type = $tpl->business_type ?? '';
            $tc = $typeColors[$type] ?? '#71717a';
            $data = json_decode(wp_json_encode($tpl->data ?? []), true) ?? [];
            $pageCount = count($data['pages'] ?? []);
            $formCount = count($data['forms'] ?? []);

            // Get theme colors for preview.
            $themeColors = $data['theme']['colors'] ?? [];
            $primary = is_array($themeColors['primary'] ?? null) ? ($themeColors['primary']['value'] ?? '#666') : ($themeColors['primary'] ?? '#666');
            $accent = is_array($themeColors['accent'] ?? null) ? ($themeColors['accent']['value'] ?? '#999') : ($themeColors['accent'] ?? '#999');
        ?>
        <div class="npb-sc" style="padding:0;overflow:hidden">
            <!-- Color bar -->
            <div style="height:4px;background:linear-gradient(90deg,<?php echo esc_attr($primary); ?>,<?php echo esc_attr($accent); ?>)"></div>

            <div style="padding:20px">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px">
                    <div>
                        <div style="font-size:17px;font-weight:700;color:#09090b;margin-bottom:2px"><?php echo esc_html($tpl->name); ?></div>
                        <div style="display:flex;gap:6px;align-items:center">
                            <code style="font-size:9px;font-weight:700;background:<?php echo $tc; ?>15;color:<?php echo $tc; ?>;padding:2px 8px;border-radius:4px"><?php echo esc_html(strtoupper($type)); ?></code>
                            <span style="font-size:11px;color:#a1a1aa"><?php echo $pageCount; ?> pages &middot; <?php echo $formCount; ?> forms</span>
                        </div>
                    </div>
                    <div style="display:flex;gap:3px">
                        <span style="width:24px;height:24px;border-radius:6px;background:<?php echo esc_attr($primary); ?>;display:inline-block;border:1px solid rgba(0,0,0,.1)"></span>
                        <span style="width:24px;height:24px;border-radius:6px;background:<?php echo esc_attr($accent); ?>;display:inline-block;border:1px solid rgba(0,0,0,.1)"></span>
                    </div>
                </div>

                <p style="font-size:12px;color:#71717a;line-height:1.5;margin-bottom:16px"><?php echo esc_html($tpl->description ?? ''); ?></p>

                <!-- Pages preview -->
                <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:16px">
                    <?php foreach (($data['pages'] ?? []) as $pg) : ?>
                        <span style="font-size:10px;padding:3px 8px;background:#f4f4f5;border-radius:4px;color:#52525b;font-weight:500"><?php echo esc_html($pg['title'] ?? $pg['slug'] ?? ''); ?></span>
                    <?php endforeach; ?>
                </div>

                <!-- Import form -->
                <form method="post" style="border-top:1px solid #f3f4f6;padding-top:14px">
                    <?php wp_nonce_field('npb_template_import'); ?>
                    <input type="hidden" name="template_slug" value="<?php echo esc_attr($tpl->slug); ?>">

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
                        <input type="text" name="biz_name" placeholder="Business Name" value="<?php echo esc_attr(get_bloginfo('name')); ?>" style="padding:6px 10px;border:1px solid #e5e7eb;border-radius:5px;font-size:11px">
                        <input type="text" name="biz_phone" placeholder="Phone" style="padding:6px 10px;border:1px solid #e5e7eb;border-radius:5px;font-size:11px">
                        <input type="email" name="biz_email" placeholder="Email" value="<?php echo esc_attr(get_option('admin_email')); ?>" style="padding:6px 10px;border:1px solid #e5e7eb;border-radius:5px;font-size:11px">
                        <input type="text" name="biz_city" placeholder="City" style="padding:6px 10px;border:1px solid #e5e7eb;border-radius:5px;font-size:11px">
                    </div>

                    <button type="submit" name="npb_import_template" value="1"
                        style="width:100%;background:linear-gradient(135deg,<?php echo esc_attr($primary); ?>,<?php echo esc_attr($accent); ?>);color:#fff;border:none;padding:10px;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;transition:opacity .15s"
                        onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'"
                    >Import This Template</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php
    }

    private function renderPagesPage(): void
    {
        $pageRepo = new \NextPressBuilder\Core\Repository\PageRepository();
        $sectionRepo = new \NextPressBuilder\Core\Repository\SectionRepository();

        // Handle create page.
        if (isset($_POST['npb_create_page']) && check_admin_referer('npb_page_action')) {
            $title = sanitize_text_field(wp_unslash($_POST['page_title'] ?? ''));
            $type = sanitize_text_field(wp_unslash($_POST['page_type'] ?? 'page'));
            if ($title) {
                $plugin = \NextPressBuilder\Plugin::instance();
                $pageService = $plugin->make(\NextPressBuilder\Modules\PageBuilder\Service\PageService::class);
                $pageService->create(['title' => $title, 'page_type' => $type]);
                echo '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#166534;font-weight:500">Page created.</div>';
            }
        }

        // Handle add section.
        if (isset($_POST['npb_add_section']) && check_admin_referer('npb_page_action')) {
            $pageId = (int) ($_POST['target_page_id'] ?? 0);
            $sectionType = sanitize_text_field(wp_unslash($_POST['section_type'] ?? ''));
            if ($pageId && $sectionType) {
                $plugin = \NextPressBuilder\Plugin::instance();
                $sectionService = $plugin->make(\NextPressBuilder\Modules\PageBuilder\Service\SectionService::class);
                $result = $sectionService->add(['page_id' => $pageId, 'section_type' => $sectionType]);
                if (is_object($result)) {
                    echo '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#166534;font-weight:500">Section added.</div>';
                } else {
                    echo '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#991b1b;font-weight:500">Error: ' . esc_html($result) . '</div>';
                }
            }
        }

        // Handle publish/unpublish.
        if (isset($_GET['publish_page']) && check_admin_referer('npb_page_action')) {
            $pageRepo->update((int) $_GET['publish_page'], ['status' => 'published']);
        }
        if (isset($_GET['unpublish_page']) && check_admin_referer('npb_page_action')) {
            $pageRepo->update((int) $_GET['unpublish_page'], ['status' => 'draft']);
        }
        if (isset($_GET['delete_page']) && check_admin_referer('npb_page_action')) {
            $sectionRepo->deleteByPage((int) $_GET['delete_page']);
            $pageRepo->delete((int) $_GET['delete_page']);
        }

        $allPages = $pageRepo->findBy([], 'title', 'ASC');

        // Component list for add-section dropdown.
        $compRepo = new \NextPressBuilder\Core\Repository\ComponentRepository();
        $components = $compRepo->findBy([], 'name', 'ASC');

        $statusColors = ['draft'=>'#f59e0b','published'=>'#16a34a','archived'=>'#6b7280'];
        $typeIcons = ['page'=>'dashicons-text-page','header'=>'dashicons-align-center','footer'=>'dashicons-align-none','component'=>'dashicons-grid-view'];
        ?>

        <!-- Create Page Form -->
        <p class="npb-sh">Create New Page</p>
        <div class="npb-sc" style="margin-bottom:20px">
            <form method="post" style="display:flex;gap:10px;align-items:flex-end">
                <?php wp_nonce_field('npb_page_action'); ?>
                <div style="flex:1">
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:4px">Page Title</label>
                    <input type="text" name="page_title" required placeholder="e.g. Home, About Us, Services" style="width:100%;padding:8px 12px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
                </div>
                <div style="width:160px">
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:4px">Type</label>
                    <select name="page_type" style="width:100%;padding:8px 12px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
                        <option value="page">Page</option>
                        <option value="header">Header</option>
                        <option value="footer">Footer</option>
                    </select>
                </div>
                <button type="submit" name="npb_create_page" value="1" style="background:#7c3aed;color:#fff;border:none;padding:8px 20px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap">+ Create</button>
            </form>
        </div>

        <!-- Pages List -->
        <p class="npb-sh">All Pages (<?php echo count($allPages); ?>)</p>
        <?php if (empty($allPages)) : ?>
            <div class="npb-sc" style="text-align:center;padding:40px;color:#a1a1aa;font-size:13px">No pages yet. Create one above.</div>
        <?php endif; ?>

        <?php foreach ($allPages as $pg) :
            $sections = $sectionRepo->findByPage((int) $pg->id);
            $sc = $statusColors[$pg->status] ?? '#6b7280';
            $icon = $typeIcons[$pg->page_type] ?? 'dashicons-text-page';
            $pubUrl = wp_nonce_url(admin_url("admin.php?page=nextpress-pages&publish_page={$pg->id}"), 'npb_page_action');
            $unpubUrl = wp_nonce_url(admin_url("admin.php?page=nextpress-pages&unpublish_page={$pg->id}"), 'npb_page_action');
            $delUrl = wp_nonce_url(admin_url("admin.php?page=nextpress-pages&delete_page={$pg->id}"), 'npb_page_action');
        ?>
        <div class="npb-sc" style="padding:0;overflow:hidden;margin-bottom:14px">
            <!-- Page header -->
            <div style="padding:16px 20px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between">
                <div style="display:flex;align-items:center;gap:10px">
                    <span class="dashicons <?php echo esc_attr($icon); ?>" style="color:#71717a;font-size:18px;width:18px;height:18px"></span>
                    <div style="font-size:15px;font-weight:700;color:#09090b"><?php echo esc_html($pg->title); ?></div>
                    <code style="font-size:10px;color:#a1a1aa">/<?php echo esc_html($pg->slug); ?></code>
                    <span style="font-size:9px;font-weight:700;padding:2px 8px;border-radius:100px;color:#fff;background:<?php echo $sc; ?>"><?php echo esc_html(strtoupper($pg->status)); ?></span>
                    <code style="font-size:9px;color:#a1a1aa"><?php echo esc_html($pg->page_type); ?></code>
                </div>
                <div style="display:flex;gap:6px;font-size:11px">
                    <?php if ($pg->status !== 'published') : ?>
                        <a href="<?php echo esc_url($pubUrl); ?>" style="color:#16a34a;text-decoration:none;font-weight:600;padding:4px 10px;background:#f0fdf4;border-radius:4px">Publish</a>
                    <?php else : ?>
                        <a href="<?php echo esc_url($unpubUrl); ?>" style="color:#f59e0b;text-decoration:none;font-weight:600;padding:4px 10px;background:#fffbeb;border-radius:4px">Unpublish</a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(rest_url("npb/v1/pages/{$pg->slug}")); ?>" target="_blank" style="color:#7c3aed;text-decoration:none;font-weight:600;padding:4px 10px;background:#faf5ff;border-radius:4px">JSON</a>
                    <a href="<?php echo esc_url($delUrl); ?>" style="color:#dc2626;text-decoration:none;font-weight:600;padding:4px 10px;background:#fef2f2;border-radius:4px" onclick="return confirm('Delete this page?')">Delete</a>
                </div>
            </div>

            <!-- Sections -->
            <div style="padding:14px 20px">
                <div style="font-size:10px;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Sections (<?php echo count($sections); ?>)</div>
                <?php if (empty($sections)) : ?>
                    <div style="color:#a1a1aa;font-size:12px;margin-bottom:10px">No sections yet.</div>
                <?php else : ?>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px">
                        <?php foreach ($sections as $s) :
                            $enabled = !empty($s->enabled);
                        ?>
                        <div style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:<?php echo $enabled ? '#f9fafb' : '#fef2f2'; ?>;border:1px solid <?php echo $enabled ? '#f3f4f6' : '#fecaca'; ?>;border-radius:6px;font-size:11px">
                            <span style="width:6px;height:6px;border-radius:50%;background:<?php echo $enabled ? '#22c55e' : '#ef4444'; ?>;display:inline-block"></span>
                            <span style="font-weight:600;color:#09090b"><?php echo esc_html($s->section_type); ?></span>
                            <span style="color:#a1a1aa;font-size:9px"><?php echo esc_html($s->variant_id ?? 'default'); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Add section form (inline) -->
                <form method="post" style="display:flex;gap:8px;align-items:center">
                    <?php wp_nonce_field('npb_page_action'); ?>
                    <input type="hidden" name="target_page_id" value="<?php echo (int) $pg->id; ?>">
                    <select name="section_type" style="padding:5px 10px;border:1px solid #e5e7eb;border-radius:5px;font-size:11px;color:#374151">
                        <option value="">+ Add section...</option>
                        <option value="container">Container (Flex/Grid)</option>
                        <?php foreach ($components as $comp) : ?>
                            <option value="<?php echo esc_attr($comp->slug); ?>"><?php echo esc_html($comp->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="npb_add_section" value="1" style="background:#09090b;color:#fff;border:none;padding:5px 14px;border-radius:5px;font-size:11px;font-weight:600;cursor:pointer">Add</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php
    }

    private function renderFormsPage(): void
    {
        $formRepo = new \NextPressBuilder\Core\Repository\FormRepository();
        $subRepo  = new \NextPressBuilder\Core\Repository\SubmissionRepository();
        $forms    = $formRepo->findBy([], 'name', 'ASC');

        $fieldTypeColors = [
            'text'=>'#16a34a','email'=>'#2563eb','phone'=>'#7c3aed','textarea'=>'#0ea5e9',
            'select'=>'#f97316','radio'=>'#ec4899','checkbox'=>'#eab308','checkbox_group'=>'#eab308',
            'file'=>'#6b7280','date'=>'#14b8a6','time'=>'#14b8a6','number'=>'#ef4444',
            'hidden'=>'#a1a1aa','html'=>'#71717a','divider'=>'#d4d4d4',
        ];
        ?>
        <!-- Stats -->
        <div style="display:flex;gap:12px;margin-bottom:20px">
            <div class="npb-sc" style="padding:14px 20px;flex:1;display:flex;align-items:center;gap:12px">
                <div style="width:38px;height:38px;border-radius:10px;background:#fef2f2;display:flex;align-items:center;justify-content:center">
                    <span class="dashicons dashicons-feedback" style="color:#dc2626;font-size:18px;width:18px;height:18px"></span>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:800;color:#09090b"><?php echo count($forms); ?></div>
                    <div style="font-size:11px;color:#71717a">Forms</div>
                </div>
            </div>
            <div class="npb-sc" style="padding:14px 20px;flex:1;display:flex;align-items:center;gap:12px">
                <div style="width:38px;height:38px;border-radius:10px;background:#eff6ff;display:flex;align-items:center;justify-content:center">
                    <span class="dashicons dashicons-email" style="color:#2563eb;font-size:18px;width:18px;height:18px"></span>
                </div>
                <div>
                    <?php $totalSubs = 0; foreach ($forms as $f) $totalSubs += $subRepo->count(['form_id' => (int)$f->id]); ?>
                    <div style="font-size:22px;font-weight:800;color:#09090b"><?php echo $totalSubs; ?></div>
                    <div style="font-size:11px;color:#71717a">Total Submissions</div>
                </div>
            </div>
            <div class="npb-sc" style="padding:14px 20px;flex:1;display:flex;align-items:center;gap:12px">
                <div style="width:38px;height:38px;border-radius:10px;background:#fefce8;display:flex;align-items:center;justify-content:center">
                    <span class="dashicons dashicons-flag" style="color:#ca8a04;font-size:18px;width:18px;height:18px"></span>
                </div>
                <div>
                    <?php $totalUnread = 0; foreach ($forms as $f) $totalUnread += $subRepo->countUnread((int)$f->id); ?>
                    <div style="font-size:22px;font-weight:800;color:#09090b"><?php echo $totalUnread; ?></div>
                    <div style="font-size:11px;color:#71717a">Unread</div>
                </div>
            </div>
        </div>

        <!-- Form cards -->
        <p class="npb-sh">All Forms</p>
        <?php foreach ($forms as $form) :
            $fields = json_decode(wp_json_encode($form->fields ?? []), true) ?? [];
            $settings = json_decode(wp_json_encode($form->settings ?? []), true) ?? [];
            $subCount = $subRepo->count(['form_id' => (int)$form->id]);
            $unread = $subRepo->countUnread((int)$form->id);
        ?>
        <div class="npb-sc" style="padding:0;overflow:hidden;margin-bottom:16px">
            <div style="padding:18px 20px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between">
                <div style="display:flex;align-items:center;gap:12px">
                    <div style="font-size:16px;font-weight:700;color:#09090b"><?php echo esc_html($form->name); ?></div>
                    <code style="font-size:10px;color:#a1a1aa"><?php echo esc_html($form->slug); ?></code>
                    <?php if ($unread > 0) : ?>
                        <span style="font-size:10px;font-weight:700;background:#ef4444;color:#fff;padding:2px 8px;border-radius:100px"><?php echo $unread; ?> new</span>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:8px;align-items:center">
                    <span style="font-size:12px;color:#71717a"><?php echo $subCount; ?> submissions</span>
                    <a href="<?php echo esc_url(rest_url("npb/v1/forms/{$form->slug}")); ?>" target="_blank" style="font-size:10px;color:#7c3aed;text-decoration:none;font-weight:600">JSON &rarr;</a>
                </div>
            </div>

            <!-- Fields -->
            <div style="padding:14px 20px">
                <div style="font-size:10px;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">Fields (<?php echo count($fields); ?>)</div>
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px">
                    <?php foreach ($fields as $field) :
                        $fType = $field['type'] ?? 'text';
                        $fLabel = $field['label'] ?? $field['key'] ?? '';
                        $fRequired = !empty($field['required']);
                        $tc = $fieldTypeColors[$fType] ?? '#71717a';
                    ?>
                    <div style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:#f9fafb;border:1px solid #f3f4f6;border-radius:6px;font-size:11px">
                        <code style="font-size:9px;font-weight:700;color:<?php echo $tc; ?>"><?php echo esc_html($fType); ?></code>
                        <span style="color:#374151;font-weight:500"><?php echo esc_html($fLabel); ?></span>
                        <?php if ($fRequired) : ?><span style="color:#ef4444;font-weight:700">*</span><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Settings summary -->
                <div style="display:flex;gap:12px;flex-wrap:wrap;font-size:11px;color:#71717a">
                    <span>Button: <strong style="color:#09090b"><?php echo esc_html($settings['submit_button'] ?? 'Submit'); ?></strong></span>
                    <?php $spam = $settings['spam_protection'] ?? []; ?>
                    <span>Spam: <strong style="color:#09090b"><?php echo ($spam['min_submit_time'] ?? 3); ?>s min</strong></span>
                    <span>Rate: <strong style="color:#09090b"><?php echo ($spam['max_submissions_per_hour'] ?? 5); ?>/hr</strong></span>
                    <?php $notif = $settings['notifications'] ?? []; ?>
                    <span>Admin email: <strong style="color:<?php echo !empty($notif['admin']['enabled']) ? '#16a34a' : '#a1a1aa'; ?>"><?php echo !empty($notif['admin']['enabled']) ? 'On' : 'Off'; ?></strong></span>
                    <span>Confirmation: <strong style="color:<?php echo !empty($notif['user_confirmation']['enabled']) ? '#16a34a' : '#a1a1aa'; ?>"><?php echo !empty($notif['user_confirmation']['enabled']) ? 'On' : 'Off'; ?></strong></span>
                </div>
            </div>

            <!-- Submissions preview -->
            <?php
            $recentSubs = $subRepo->findByForm((int)$form->id);
            $recentSubs = array_slice($recentSubs, 0, 3);
            if (!empty($recentSubs)) :
            ?>
            <div style="border-top:1px solid #f3f4f6;padding:14px 20px;background:#fafafa">
                <div style="font-size:10px;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Recent Submissions</div>
                <?php foreach ($recentSubs as $sub) :
                    $subData = is_object($sub->data) ? (array)$sub->data : ($sub->data ?? []);
                    $statusColors = ['unread'=>'#f59e0b','read'=>'#16a34a','starred'=>'#7c3aed','spam'=>'#ef4444','archived'=>'#6b7280'];
                    $sc = $statusColors[$sub->status] ?? '#71717a';
                ?>
                <div style="display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid #f0f0f0;font-size:12px">
                    <span style="width:8px;height:8px;border-radius:50%;background:<?php echo $sc; ?>;flex-shrink:0" title="<?php echo esc_attr($sub->status); ?>"></span>
                    <span style="color:#09090b;font-weight:500;min-width:120px"><?php echo esc_html($subData['full_name'] ?? $subData['name'] ?? 'Anonymous'); ?></span>
                    <span style="color:#71717a;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html($subData['email'] ?? $subData['message'] ?? ''); ?></span>
                    <span style="color:#a1a1aa;font-size:10px;flex-shrink:0"><?php echo esc_html($sub->created_at); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <!-- Test submission link -->
        <div style="margin-top:8px;padding:16px;background:#f9fafb;border-radius:8px;border:1px dashed #e5e7eb;font-size:12px;color:#71717a">
            <strong>Test:</strong> Submit via API:
            <code style="background:#fff;padding:2px 6px;border-radius:3px;font-size:10px;color:#09090b;margin-left:4px">
                POST /wp-json/npb/v1/forms/contact/submit {"full_name":"Test","email":"test@test.com","message":"Hello world test"}
            </code>
        </div>
        <?php
    }

    private function renderNavigationPage(): void
    {
        $navRepo = new \NextPressBuilder\Core\Repository\NavigationRepository();
        $menus = $navRepo->findBy([], 'name', 'ASC');

        $locColors = ['header'=>'#7c3aed','footer'=>'#2563eb','sidebar'=>'#16a34a','custom'=>'#f97316'];
        ?>
        <p class="npb-sh">Navigation Menus (<?php echo count($menus); ?>)</p>

        <?php if (empty($menus)) : ?>
            <div class="npb-sc" style="text-align:center;padding:40px;color:#a1a1aa;font-size:13px">No menus created yet.</div>
        <?php endif; ?>

        <?php foreach ($menus as $menu) :
            $loc = $menu->location ?? 'custom';
            $lc = $locColors[$loc] ?? '#71717a';
            $items = is_array($menu->items) ? $menu->items : (is_object($menu->items) ? (array)$menu->items : []);
        ?>
        <div class="npb-sc" style="padding:0;overflow:hidden;margin-bottom:16px">
            <div style="padding:16px 20px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between">
                <div style="display:flex;align-items:center;gap:12px">
                    <div style="font-size:15px;font-weight:700;color:#09090b"><?php echo esc_html($menu->name); ?></div>
                    <code style="background:<?php echo $lc; ?>15;color:<?php echo $lc; ?>;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700"><?php echo esc_html($loc); ?></code>
                    <code style="color:#a1a1aa;font-size:10px"><?php echo esc_html($menu->slug); ?></code>
                </div>
                <a href="<?php echo esc_url(rest_url("npb/v1/navigation/{$menu->slug}")); ?>" target="_blank" style="font-size:10px;color:#7c3aed;text-decoration:none;font-weight:600">View JSON &rarr;</a>
            </div>
            <div style="padding:12px 20px">
                <?php $this->renderMenuItems($items, 0); ?>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Button Presets Section -->
        <?php
        $btnRepo = new \NextPressBuilder\Core\Repository\ButtonRepository();
        $buttons = $btnRepo->findBy([], 'name', 'ASC');
        ?>
        <p class="npb-sh" style="margin-top:8px">Button Presets (<?php echo count($buttons); ?>)</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px">
            <?php foreach ($buttons as $btn) :
                $p = is_object($btn->preset) ? $btn->preset : new \stdClass();
                $bg = $p->bg ?? '#666';
                $color = $p->color ?? '#fff';
                $radius = $p->radius ?? '8px';
                $padding = $p->padding ?? '12px 24px';
                $fontSize = $p->fontSize ?? '14px';
                $fontWeight = $p->fontWeight ?? '600';
                $border = $p->border ?? 'none';
                $shadow = $p->shadow ?? 'none';
                // Resolve CSS vars for preview.
                $previewBg = str_contains($bg, 'var(') ? '#1E3A5F' : $bg;
                $previewBg = str_contains($previewBg, 'gradient') ? 'linear-gradient(135deg,#1E3A5F,#D4942A)' : $previewBg;
                $previewColor = str_contains($color, 'var(') ? '#1E3A5F' : $color;
                if ($bg === 'transparent' || $bg === '#FFFFFF') $previewColor = '#1E3A5F';
                $previewRadius = str_contains($radius, 'var(') ? '8px' : $radius;
            ?>
            <div class="npb-sc" style="padding:16px;display:flex;flex-direction:column;gap:12px">
                <div style="display:flex;align-items:center;justify-content:space-between">
                    <div>
                        <div style="font-size:13px;font-weight:700;color:#09090b"><?php echo esc_html($btn->name); ?></div>
                        <code style="font-size:10px;color:#a1a1aa"><?php echo esc_html($btn->slug); ?></code>
                    </div>
                    <?php if (!empty($btn->is_default)) : ?>
                        <span style="font-size:9px;font-weight:600;background:#f0fdf4;color:#16a34a;padding:2px 6px;border-radius:3px">DEFAULT</span>
                    <?php endif; ?>
                </div>
                <!-- Button preview -->
                <div style="text-align:center;padding:12px;background:#f9fafb;border-radius:8px">
                    <span style="display:inline-block;background:<?php echo esc_attr($previewBg); ?>;color:<?php echo esc_attr($previewColor); ?>;border-radius:<?php echo esc_attr($previewRadius); ?>;padding:<?php echo esc_attr($padding); ?>;font-size:<?php echo esc_attr($fontSize); ?>;font-weight:<?php echo esc_attr($fontWeight); ?>;border:<?php echo esc_attr($border === 'none' ? 'none' : '2px solid #1E3A5F'); ?>">Button</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Recursively render menu items with indentation.
     *
     * @param array<int, mixed> $items
     */
    private function renderMenuItems(array $items, int $depth): void
    {
        foreach ($items as $item) {
            $item = (object) $item;
            $indent = $depth * 24;
            $hasChildren = !empty($item->children);
            $typeColors = ['page_link'=>'#16a34a','custom_url'=>'#2563eb','section_anchor'=>'#7c3aed','phone'=>'#f97316','email'=>'#ec4899','dropdown'=>'#6b7280'];
            $tc = $typeColors[$item->type ?? ''] ?? '#71717a';
            ?>
            <div style="display:flex;align-items:center;gap:8px;padding:5px 0;margin-left:<?php echo $indent; ?>px">
                <?php if ($hasChildren) : ?>
                    <span style="color:#a1a1aa;font-size:12px">&#9662;</span>
                <?php else : ?>
                    <span style="width:6px;height:6px;border-radius:50%;background:<?php echo $tc; ?>;display:inline-block;flex-shrink:0"></span>
                <?php endif; ?>
                <span style="font-size:13px;font-weight:<?php echo $hasChildren ? '600' : '400'; ?>;color:#09090b"><?php echo esc_html($item->label ?? ''); ?></span>
                <code style="font-size:10px;color:#a1a1aa"><?php echo esc_html($item->url ?? ''); ?></code>
                <code style="font-size:9px;background:<?php echo $tc; ?>15;color:<?php echo $tc; ?>;padding:1px 5px;border-radius:3px"><?php echo esc_html($item->type ?? ''); ?></code>
            </div>
            <?php
            if ($hasChildren) {
                $children = is_array($item->children) ? $item->children : (array)$item->children;
                $this->renderMenuItems($children, $depth + 1);
            }
        }
    }

    /**
     * Get sub-page definitions.
     *
     * @return array<int, array{title: string, menu_title: string, capability: string, slug: string}>
     */
    private function getSubPages(): array
    {
        return [
            [
                'title'      => __( 'Dashboard', 'nextpress-builder' ),
                'menu_title' => __( 'Dashboard', 'nextpress-builder' ),
                'capability' => Capability::EDIT_PAGES,
                'slug'       => self::MENU_SLUG, // Same as parent = first item becomes "Dashboard".
            ],
            [
                'title'      => __( 'Pages', 'nextpress-builder' ),
                'menu_title' => __( 'Pages', 'nextpress-builder' ),
                'capability' => Capability::EDIT_PAGES,
                'slug'       => 'nextpress-pages',
            ],
            [
                'title'      => __( 'Headers', 'nextpress-builder' ),
                'menu_title' => __( 'Headers', 'nextpress-builder' ),
                'capability' => Capability::EDIT_PAGES,
                'slug'       => 'nextpress-headers',
            ],
            [
                'title'      => __( 'Footers', 'nextpress-builder' ),
                'menu_title' => __( 'Footers', 'nextpress-builder' ),
                'capability' => Capability::EDIT_PAGES,
                'slug'       => 'nextpress-footers',
            ],
            [
                'title'      => __( 'Forms', 'nextpress-builder' ),
                'menu_title' => __( 'Forms', 'nextpress-builder' ),
                'capability' => Capability::MANAGE_FORMS,
                'slug'       => 'nextpress-forms',
            ],
            [
                'title'      => __( 'Components', 'nextpress-builder' ),
                'menu_title' => __( 'Components', 'nextpress-builder' ),
                'capability' => Capability::MANAGE_COMPONENTS,
                'slug'       => 'nextpress-components',
            ],
            [
                'title'      => __( 'Templates', 'nextpress-builder' ),
                'menu_title' => __( 'Templates', 'nextpress-builder' ),
                'capability' => Capability::MANAGE_TEMPLATES,
                'slug'       => 'nextpress-templates',
            ],
            [
                'title'      => __( 'Theme', 'nextpress-builder' ),
                'menu_title' => __( 'Theme', 'nextpress-builder' ),
                'capability' => Capability::MANAGE_THEMES,
                'slug'       => 'nextpress-theme',
            ],
            [
                'title'      => __( 'SEO', 'nextpress-builder' ),
                'menu_title' => __( 'SEO', 'nextpress-builder' ),
                'capability' => Capability::MANAGE_SEO,
                'slug'       => 'nextpress-seo',
            ],
            [
                'title'      => __( 'Navigation', 'nextpress-builder' ),
                'menu_title' => __( 'Navigation', 'nextpress-builder' ),
                'capability' => Capability::MANAGE_NAVIGATION,
                'slug'       => 'nextpress-navigation',
            ],
            [
                'title'      => __( 'Settings', 'nextpress-builder' ),
                'menu_title' => __( 'Settings', 'nextpress-builder' ),
                'capability' => Capability::MANAGE_SETTINGS,
                'slug'       => 'nextpress-settings',
            ],
        ];
    }

    /**
     * Get the main menu slug.
     */
    public static function menuSlug(): string
    {
        return self::MENU_SLUG;
    }
}
