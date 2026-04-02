<?php

declare(strict_types=1);

namespace NextPressBuilder\Core;

use NextPressBuilder\Core\Rest\Middleware\Authentication;
use NextPressBuilder\Core\Rest\Middleware\CacheHeaders;
use NextPressBuilder\Core\Rest\Middleware\RateLimiter;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Registers and manages all REST API routes for the plugin.
 *
 * All endpoints live under the /wp-json/npb/v1/ namespace.
 * Applies middleware: rate limiting, cache headers, CORS, JWT auth.
 */
class RestApiManager
{
    public const NAMESPACE = 'npb/v1';

    private RateLimiter $rateLimiter;
    private CacheHeaders $cacheHeaders;
    private Authentication $auth;

    public function __construct(
        private readonly Container $container,
        private readonly HookManager $hooks,
    ) {
        $settings = $container->make(SettingsManager::class);
        $this->rateLimiter = new RateLimiter($settings);
        $this->cacheHeaders = new CacheHeaders();
        $this->auth = new Authentication();
    }

    /**
     * Initialize REST API hooks.
     */
    public function init(): void
    {
        $this->hooks->addAction('rest_api_init', [$this, 'registerRoutes']);

        // Register JWT auth filter.
        $this->hooks->addFilter('determine_current_user', [$this->auth, 'authenticateRequest'], 20);

        // CORS headers for configured Next.js domain.
        $this->hooks->addAction('rest_api_init', [$this, 'handleCors']);

        // Apply middleware to all npb responses.
        $this->hooks->addFilter('rest_post_dispatch', [$this, 'applyMiddleware'], 10, 3);
    }

    /**
     * Register all REST routes (core + auth + module routes).
     */
    public function registerRoutes(): void
    {
        $this->registerCoreRoutes();
        $this->registerAuthRoutes();
        $this->registerSettingsRoutes();
        $this->registerModuleRoutes();
    }

    /**
     * Register core public endpoints.
     */
    private function registerCoreRoutes(): void
    {
        register_rest_route(self::NAMESPACE, '/health', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'healthCheck'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::NAMESPACE, '/site-config', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'siteConfig'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Register JWT auth endpoints.
     */
    private function registerAuthRoutes(): void
    {
        // Generate JWT token.
        register_rest_route(self::NAMESPACE, '/auth/token', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'generateToken'],
            'permission_callback' => '__return_true',
        ]);

        // Refresh JWT token.
        register_rest_route(self::NAMESPACE, '/auth/refresh', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'refreshToken'],
            'permission_callback' => '__return_true',
        ]);

        // Verify current token / get current user.
        register_rest_route(self::NAMESPACE, '/auth/me', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'currentUser'],
            'permission_callback' => 'is_user_logged_in',
        ]);
    }

    /**
     * Register settings endpoints (admin only).
     */
    private function registerSettingsRoutes(): void
    {
        register_rest_route(self::NAMESPACE, '/settings', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'getSettings'],
                'permission_callback' => fn() => Capability::canManageSettings(),
            ],
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'updateSettings'],
                'permission_callback' => fn() => Capability::canManageSettings(),
            ],
        ]);
    }

    /**
     * Register routes from all loaded modules.
     */
    private function registerModuleRoutes(): void
    {
        /** @var ModuleManager $moduleManager */
        $moduleManager = $this->container->make(ModuleManager::class);
        $routes = $moduleManager->collectRoutes();

        foreach ($routes as $route) {
            $method = match (strtoupper($route['method'] ?? 'GET')) {
                'POST'   => WP_REST_Server::CREATABLE,
                'PUT'    => WP_REST_Server::EDITABLE,
                'DELETE' => WP_REST_Server::DELETABLE,
                default  => WP_REST_Server::READABLE,
            };

            register_rest_route(self::NAMESPACE, $route['path'], [
                'methods'             => $method,
                'callback'            => $route['callback'],
                'permission_callback' => $route['permission'] ?? '__return_true',
                'args'                => $route['args'] ?? [],
            ]);
        }
    }

    // ── Middleware ─────────────────────────────────────────────

    /**
     * Apply rate limiting + cache headers to all npb/ responses.
     */
    public function applyMiddleware(WP_REST_Response $response, WP_REST_Server $server, WP_REST_Request $request): WP_REST_Response
    {
        $route = $request->get_route();

        // Only apply to our namespace.
        if (!str_starts_with($route, '/' . self::NAMESPACE)) {
            return $response;
        }

        // Determine tier.
        $isAdmin = is_user_logged_in();
        $isForm = str_contains($route, '/submit');
        $method = $request->get_method();

        // Rate limiting.
        $tier = $isForm ? 'forms' : ($isAdmin ? 'admin' : 'public');
        $allowed = $this->rateLimiter->check($request, $tier);
        $this->rateLimiter->applyHeaders($response);

        if (!$allowed) {
            return $this->rateLimiter->tooManyRequests();
        }

        // Cache headers.
        if ($method === 'GET' && !$isAdmin) {
            // Long cache for theme and component data.
            $isLongCache = str_contains($route, '/theme') || str_contains($route, '/components');
            $cacheTier = $isLongCache ? 'long' : 'public';
            $this->cacheHeaders->apply($response, $cacheTier);
            $this->cacheHeaders->addETag($response);
        } else {
            $this->cacheHeaders->apply($response, 'private');
        }

        return $response;
    }

    /**
     * Set CORS headers for the configured Next.js frontend domain.
     */
    public function handleCors(): void
    {
        /** @var SettingsManager $settings */
        $settings = $this->container->make(SettingsManager::class);
        $nextjsUrl = $settings->getString('nextjs_frontend_url');

        if (!$nextjsUrl) {
            return;
        }

        $origin = rtrim($nextjsUrl, '/');

        remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
        add_filter('rest_pre_serve_request', function ($value) use ($origin) {
            $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

            if ($requestOrigin === $origin) {
                header("Access-Control-Allow-Origin: {$origin}");
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Max-Age: 86400');
            }

            return $value;
        });
    }

    // ── Endpoint Handlers ─────────────────────────────────────

    /**
     * GET /npb/v1/health
     */
    public function healthCheck(WP_REST_Request $request): WP_REST_Response
    {
        /** @var ModuleManager $moduleManager */
        $moduleManager = $this->container->make(ModuleManager::class);
        /** @var SettingsManager $settings */
        $settings = $this->container->make(SettingsManager::class);

        $modules = [];
        foreach ($moduleManager->all() as $slug => $module) {
            $modules[] = [
                'slug'    => $slug,
                'name'    => $module->name(),
                'version' => $module->version(),
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data'    => [
                'status'        => 'ok',
                'version'       => NPB_VERSION,
                'php_version'   => PHP_VERSION,
                'wp_version'    => get_bloginfo('version'),
                'db_version'    => $settings->getString('db_version', '0'),
                'modules'       => $modules,
                'api_namespace' => self::NAMESPACE,
                'timestamp'     => gmdate('c'),
            ],
        ], 200);
    }

    /**
     * GET /npb/v1/site-config
     */
    public function siteConfig(WP_REST_Request $request): WP_REST_Response
    {
        /** @var SettingsManager $settings */
        $settings = $this->container->make(SettingsManager::class);

        return new WP_REST_Response([
            'success' => true,
            'data'    => [
                'site_name'    => get_bloginfo('name'),
                'site_tagline' => get_bloginfo('description'),
                'site_url'     => home_url(),
                'admin_email'  => get_option('admin_email'),
                'language'     => get_locale(),
                'timezone'     => wp_timezone_string(),
                'business'     => [
                    'name'      => $settings->getString('business_name', get_bloginfo('name')),
                    'phone'     => $settings->getString('business_phone'),
                    'email'     => $settings->getString('business_email', get_option('admin_email')),
                    'address'   => $settings->getString('business_address'),
                    'city'      => $settings->getString('business_city'),
                    'state'     => $settings->getString('business_state'),
                    'zip'       => $settings->getString('business_zip'),
                    'country'   => $settings->getString('business_country'),
                    'latitude'  => $settings->getString('business_latitude'),
                    'longitude' => $settings->getString('business_longitude'),
                ],
                'nextjs_url' => $settings->getString('nextjs_frontend_url'),
            ],
        ], 200);
    }

    /**
     * POST /npb/v1/auth/token — Generate JWT from username/password.
     */
    public function generateToken(WP_REST_Request $request): WP_REST_Response
    {
        $username = $request->get_param('username') ?? '';
        $password = $request->get_param('password') ?? '';

        if (!$username || !$password) {
            return self::error('Username and password are required.', 400);
        }

        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            return self::error('Invalid credentials.', 401);
        }

        $tokens = $this->auth->generateTokens($user);

        return new WP_REST_Response([
            'success' => true,
            'data'    => $tokens,
        ], 200);
    }

    /**
     * POST /npb/v1/auth/refresh — Refresh JWT using refresh token.
     */
    public function refreshToken(WP_REST_Request $request): WP_REST_Response
    {
        $refreshToken = $request->get_param('refresh_token') ?? '';

        if (!$refreshToken) {
            return self::error('Refresh token is required.', 400);
        }

        $tokens = $this->auth->refreshTokens($refreshToken);

        if ($tokens === null) {
            return self::error('Invalid or expired refresh token.', 401);
        }

        return new WP_REST_Response([
            'success' => true,
            'data'    => $tokens,
        ], 200);
    }

    /**
     * GET /npb/v1/auth/me — Get current authenticated user info.
     */
    public function currentUser(WP_REST_Request $request): WP_REST_Response
    {
        $user = wp_get_current_user();

        $capabilities = [];
        foreach (Capability::all() as $cap) {
            $capabilities[$cap] = current_user_can($cap);
        }

        return new WP_REST_Response([
            'success' => true,
            'data'    => [
                'id'           => $user->ID,
                'username'     => $user->user_login,
                'email'        => $user->user_email,
                'display_name' => $user->display_name,
                'roles'        => $user->roles,
                'capabilities' => $capabilities,
            ],
        ], 200);
    }

    /**
     * GET /npb/v1/settings — Get all plugin settings (admin only).
     */
    public function getSettings(WP_REST_Request $request): WP_REST_Response
    {
        /** @var SettingsManager $settings */
        $settings = $this->container->make(SettingsManager::class);

        return new WP_REST_Response([
            'success' => true,
            'data'    => [
                'nextjs_frontend_url'       => $settings->getString('nextjs_frontend_url'),
                'nextjs_revalidation_url'   => $settings->getString('nextjs_revalidation_url'),
                'nextjs_revalidation_secret' => $settings->getString('nextjs_revalidation_secret'),
                'business_name'             => $settings->getString('business_name'),
                'business_phone'            => $settings->getString('business_phone'),
                'business_email'            => $settings->getString('business_email'),
                'business_address'          => $settings->getString('business_address'),
                'business_city'             => $settings->getString('business_city'),
                'business_state'            => $settings->getString('business_state'),
                'business_zip'              => $settings->getString('business_zip'),
                'business_country'          => $settings->getString('business_country'),
                'business_latitude'         => $settings->getString('business_latitude'),
                'business_longitude'        => $settings->getString('business_longitude'),
                'rate_limit_public'         => $settings->getInt('rate_limit_public', 60),
                'rate_limit_forms'          => $settings->getInt('rate_limit_forms', 5),
                'rate_limit_admin'          => $settings->getInt('rate_limit_admin', 120),
            ],
        ], 200);
    }

    /**
     * PUT /npb/v1/settings — Update plugin settings (admin only).
     */
    public function updateSettings(WP_REST_Request $request): WP_REST_Response
    {
        /** @var SettingsManager $settings */
        $settings = $this->container->make(SettingsManager::class);
        $sanitizer = $this->container->make(Sanitizer::class);

        $allowedKeys = [
            'nextjs_frontend_url', 'nextjs_revalidation_url', 'nextjs_revalidation_secret',
            'business_name', 'business_phone', 'business_email', 'business_address',
            'business_city', 'business_state', 'business_zip', 'business_country',
            'business_latitude', 'business_longitude',
            'rate_limit_public', 'rate_limit_forms', 'rate_limit_admin',
        ];

        $data = $request->get_json_params();
        $updated = [];

        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $data)) {
                $value = $data[$key];

                // Sanitize based on key type.
                if (str_contains($key, 'url')) {
                    $value = $sanitizer->url((string) $value);
                } elseif (str_contains($key, 'email')) {
                    $value = $sanitizer->email((string) $value);
                } elseif (str_starts_with($key, 'rate_limit_')) {
                    $value = $sanitizer->absint($value);
                } else {
                    $value = $sanitizer->text((string) $value);
                }

                $settings->set($key, $value);
                $updated[] = $key;
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => count($updated) . ' setting(s) updated.',
            'data'    => ['updated' => $updated],
        ], 200);
    }

    // ── Static Helpers ────────────────────────────────────────

    public function getNamespace(): string
    {
        return self::NAMESPACE;
    }

    public static function success(mixed $data = null, int $status = 200): WP_REST_Response
    {
        return new WP_REST_Response(['success' => true, 'data' => $data], $status);
    }

    public static function error(string $message, int $status = 400, ?array $errors = null): WP_REST_Response
    {
        $body = ['success' => false, 'message' => $message];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }
        return new WP_REST_Response($body, $status);
    }

    /**
     * Get the Authentication middleware instance.
     */
    public function getAuth(): Authentication
    {
        return $this->auth;
    }

    /**
     * Get the RateLimiter middleware instance.
     */
    public function getRateLimiter(): RateLimiter
    {
        return $this->rateLimiter;
    }
}
